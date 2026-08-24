<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed. Use POST.'], 405);
}

$body = getJsonBody();
requireFields($body, ['table', 'subtotal', 'gst', 'total', 'payment_method', 'items'], 'Complete Payment');

$table = trim((string)$body['table']);
$subtotal = floatval($body['subtotal']);
$discount = floatval($body['discount'] ?? 0);
$discountType = trim((string)($body['discount_type'] ?? ''));
$gst = floatval($body['gst']);
$total = floatval($body['total']);
$paymentMethod = trim((string)$body['payment_method']);
$items = $body['items'];

$pdo = getDB();

try {
    $pdo->beginTransaction();

    // 1. Check for all active unbilled orders on this table
    $stmt = $pdo->prepare("
        SELECT id, order_ref, persons, customer_notes, created_at
        FROM orders
        WHERE table_no = ? AND payment_method IS NULL AND status != 'served'
        ORDER BY created_at ASC
    ");
    $stmt->execute([$table]);
    $activeOrders = $stmt->fetchAll();

    $now = date('Y-m-d H:i:s');

    if (!empty($activeOrders)) {
        $primaryOrder = $activeOrders[0];
        $orderId = (int)$primaryOrder['id'];
        $orderRef = $primaryOrder['order_ref'];
        $allOrderIds = array_column($activeOrders, 'id');

        // Update the primary order in the database
        $updateStmt = $pdo->prepare("
            UPDATE orders
            SET status = 'served',
                subtotal = ?,
                discount_amount = ?,
                discount_type = ?,
                gst_amount = ?,
                total_amount = ?,
                payment_method = ?,
                served_at = ?,
                updated_at = ?
            WHERE id = ?
        ");
        $updateStmt->execute([
            $subtotal,
            $discount,
            $discountType ?: null,
            $gst,
            $total,
            $paymentMethod,
            $now,
            $now,
            $orderId
        ]);

        // If there were multiple order records for this table session, mark additional orders as served
        if (count($allOrderIds) > 1) {
            $otherIds = array_slice($allOrderIds, 1);
            $placeholders = implode(',', array_fill(0, count($otherIds), '?'));
            $otherUpdate = $pdo->prepare("
                UPDATE orders
                SET status = 'served',
                    subtotal = 0,
                    discount_amount = 0,
                    gst_amount = 0,
                    total_amount = 0,
                    payment_method = ?,
                    served_at = ?,
                    updated_at = ?
                WHERE id IN ($placeholders)
            ");
            $otherUpdate->execute(array_merge([$paymentMethod, $now, $now], $otherIds));
        }

        // Clean out existing order items for all active orders of this table session to replace with final bill items
        $allPlaceholders = implode(',', array_fill(0, count($allOrderIds), '?'));
        $delStmt = $pdo->prepare("DELETE FROM order_items WHERE order_id IN ($allPlaceholders)");
        $delStmt->execute($allOrderIds);

    } else {
        if ($total <= 0 || empty($items)) {
            // Just release table from occupied_tables
            $delOcc = $pdo->prepare("DELETE FROM occupied_tables WHERE table_no = ?");
            $delOcc->execute([(int)$table]);
            $pdo->commit();
            jsonResponse([
                'success' => true,
                'order_id' => 0,
                'order_ref' => 'NONE',
                'message' => 'Table released successfully.'
            ]);
            exit;
        }

        // Create a new order directly as 'served' (billing only order)
        $todayStr = date('ymd');
        $prefix = 'ORD-' . $todayStr . '-T' . $table;
        // Count previous orders for this table today
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_ref LIKE ?");
        $countStmt->execute([$prefix . '-%']);
        $prevCount = (int)$countStmt->fetchColumn();
        $orderRef = $prefix . '-' . str_pad((string)($prevCount + 1), 3, '0', STR_PAD_LEFT);

        $insStmt = $pdo->prepare("
            INSERT INTO orders (order_ref, table_no, persons, status, subtotal, discount_amount, discount_type, gst_amount, total_amount, payment_method, served_at, created_at, updated_at)
            VALUES (?, ?, 1, 'served', ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insStmt->execute([
            $orderRef,
            $table,
            $subtotal,
            $discount,
            $discountType ?: null,
            $gst,
            $total,
            $paymentMethod,
            $now,
            $now,
            $now
        ]);
        $orderId = (int)$pdo->lastInsertId();
    }

    // Insert items into order_items
    $batchId = 'B' . time() . '_bill';
    foreach ($items as $item) {
        $itemName = trim($item['name']);
        $itemQty = max(1, (int)$item['qty']);
        $itemPrice = floatval($item['price']);
        $menuItemId = isset($item['menu_item_id']) ? (int)$item['menu_item_id'] : 0;
        $category = 'main_course';

        if ($menuItemId > 0) {
            $menuStmt = $pdo->prepare("SELECT id, category FROM menu_items WHERE id = ? LIMIT 1");
            $menuStmt->execute([$menuItemId]);
            $menuItem = $menuStmt->fetch();
            if ($menuItem) {
                $category = $menuItem['category'];
            }
        }

        if ($menuItemId === 0) {
            // Multilingual matching: check name_en, name_hi, name_mr
            $menuStmt = $pdo->prepare("
                SELECT id, category 
                FROM menu_items 
                WHERE name_en = ? OR name_hi = ? OR name_mr = ?
                   OR LOWER(name_en) = LOWER(?)
                LIMIT 1
            ");
            $menuStmt->execute([$itemName, $itemName, $itemName, $itemName]);
            $menuItem = $menuStmt->fetch();
            if ($menuItem) {
                $menuItemId = (int)$menuItem['id'];
                $category = $menuItem['category'];
            }
        }

        // If still not found, fallback to first available menu item ID to satisfy constraints
        if ($menuItemId === 0) {
            $fallbackId = $pdo->query("SELECT id FROM menu_items LIMIT 1")->fetchColumn();
            $menuItemId = $fallbackId ? (int)$fallbackId : 1;
        }

        $insItemStmt = $pdo->prepare("
            INSERT INTO order_items (order_id, batch_id, menu_item_id, item_name, category, unit_price, qty, is_new, added_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)
        ");
        $insItemStmt->execute([
            $orderId,
            $batchId,
            $menuItemId,
            $itemName,
            $category,
            $itemPrice,
            $itemQty,
            $now
        ]);
    }

    // 2. Delete from occupied_tables
    $delOcc = $pdo->prepare("DELETE FROM occupied_tables WHERE table_no = ?");
    $delOcc->execute([(int)$table]);

    // 3. Log status change history
    $histStmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, note, changed_at) VALUES (?, 'served', 'Bill completed and payment processed', ?)");
    $histStmt->execute([$orderId, $now]);

    $pdo->commit();
    jsonResponse([
        'success' => true,
        'order_id' => $orderId,
        'order_ref' => $orderRef,
        'message' => 'Payment completed and table released successfully.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(['success' => false, 'error' => 'Failed to complete payment in database.', 'detail' => $e->getMessage()], 500);
}

