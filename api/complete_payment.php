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

    // 1. Check for active order on this table
    $stmt = $pdo->prepare("
        SELECT id, order_ref, persons
        FROM orders
        WHERE table_no = ? AND status != 'served'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$table]);
    $order = $stmt->fetch();

    $now = date('Y-m-d H:i:s');

    if ($order) {
        $orderId = (int)$order['id'];
        $orderRef = $order['order_ref'];

        // Update the order in the database
        $updateStmt = $pdo->prepare("
            UPDATE orders
            SET status = 'served',
                subtotal = :subtotal,
                discount_amount = :discount,
                discount_type = :discount_type,
                gst_amount = :gst,
                total_amount = :total,
                payment_method = :payment_method,
                served_at = :now,
                updated_at = :now
            WHERE id = :id
        ");
        $updateStmt->execute([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'discount_type' => $discountType ?: null,
            'gst' => $gst,
            'total' => $total,
            'payment_method' => $paymentMethod,
            'now' => $now,
            'id' => $orderId
        ]);

        // Clean out existing order items for this order to replace them with the final bill items
        $delStmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $delStmt->execute([$orderId]);

    } else {
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
            VALUES (:ref, :table, 1, 'served', :subtotal, :discount, :discount_type, :gst, :total, :payment_method, :now, :now, :now)
        ");
        $insStmt->execute([
            'ref' => $orderRef,
            'table' => $table,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'discount_type' => $discountType ?: null,
            'gst' => $gst,
            'total' => $total,
            'payment_method' => $paymentMethod,
            'now' => $now
        ]);
        $orderId = (int)$pdo->lastInsertId();
    }

    // Insert items into order_items
    $batchId = 'B' . time() . '_bill';
    foreach ($items as $item) {
        $itemName = trim($item['name']);
        $itemQty = (int)$item['qty'];
        $itemPrice = floatval($item['price']);

        // Try to find the menu item id
        $menuStmt = $pdo->prepare("SELECT id, category FROM menu_items WHERE name_en = ? LIMIT 1");
        $menuStmt->execute([$itemName]);
        $menuItem = $menuStmt->fetch();

        $menuItemId = $menuItem ? (int)$menuItem['id'] : 0;
        $category = $menuItem ? $menuItem['category'] : 'main_course';

        if ($menuItemId === 0) {
            // Fallback: search by name case-insensitive
            $menuStmt = $pdo->prepare("SELECT id, category FROM menu_items WHERE LOWER(name_en) = LOWER(?) LIMIT 1");
            $menuStmt->execute([$itemName]);
            $menuItem = $menuStmt->fetch();
            if ($menuItem) {
                $menuItemId = (int)$menuItem['id'];
                $category = $menuItem['category'];
            }
        }

        // If still not found, we insert a placeholder/guest item or map to id 1 if table is empty
        if ($menuItemId === 0) {
            // Find any item to avoid FK constraint error
            $menuItemId = (int)$pdo->query("SELECT id FROM menu_items LIMIT 1")->fetchColumn();
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
