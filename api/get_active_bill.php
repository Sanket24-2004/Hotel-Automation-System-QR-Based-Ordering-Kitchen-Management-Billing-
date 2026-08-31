<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$pdo = getDB();
$table = trim($_GET['table'] ?? '');
$token = trim($_GET['token'] ?? '');
$isStaff = !empty($_GET['is_staff']) || !empty($_GET['is_owner']) || !empty($_GET['is_admin']);

if ($table === '') {
    jsonResponse(['success' => false, 'error' => 'Missing table number.'], 400);
}

// Table security verification: if not staff, must provide matching table token
if (!$isStaff && !verifyTableSecurityToken($table, $token)) {
    jsonResponse([
        'success'      => false,
        'unauthorized' => true,
        'error'        => 'Unauthorized: Invalid or missing Table Security Token. Please scan your table QR code.'
    ], 403);
}

try {
    // Find active unbilled orders for this table (today only — prevents ghost orders from old sessions reappearing)
    $stmt = $pdo->prepare("
        SELECT id, order_ref, table_no, persons, subtotal, gst_amount, total_amount, customer_notes, created_at
        FROM orders
        WHERE table_no = ? AND payment_method IS NULL AND DATE(created_at) = CURDATE()
        ORDER BY created_at ASC
    ");
    $stmt->execute([$table]);
    $orders = $stmt->fetchAll();

    if (empty($orders)) {
        // Return active occupation record if it exists but no order yet
        $occStmt = $pdo->prepare("SELECT occupied_at, persons FROM occupied_tables WHERE table_no = ?");
        $occStmt->execute([$table]);
        $occ = $occStmt->fetch();

        jsonResponse([
            'success' => true,
            'exists' => false,
            'persons' => $occ ? (int)$occ['persons'] : 1,
            'occupied_at' => $occ ? $occ['occupied_at'] : null,
            'items' => []
        ]);
    }

    $primaryOrder = $orders[0];
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

    // Fetch items for all unbilled orders on this table
    $itemStmt = $pdo->prepare("
        SELECT oi.item_name AS name, oi.qty, oi.unit_price AS price, oi.menu_item_id,
               COALESCE(mi.name_en, oi.item_name) AS name_en,
               COALESCE(mi.name_hi, oi.item_name) AS name_hi,
               COALESCE(mi.name_mr, oi.item_name) AS name_mr
        FROM order_items oi
        LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
        WHERE oi.order_id IN ($placeholders)
        ORDER BY oi.added_at ASC
    ");
    $itemStmt->execute($orderIds);
    $rawItems = $itemStmt->fetchAll();

    // Group items by name and price
    $groupedItems = [];
    $combinedSubtotal = 0.0;
    foreach ($rawItems as $it) {
        $name = $it['name'];
        $qty = (int)$it['qty'];
        $price = (float)$it['price'];
        $menuId = (int)($it['menu_item_id'] ?? 0);

        $key = $name . '_' . $price;
        if (!isset($groupedItems[$key])) {
            $groupedItems[$key] = [
                'name' => $name,
                'name_en' => $it['name_en'] ?? $name,
                'name_hi' => $it['name_hi'] ?? $name,
                'name_mr' => $it['name_mr'] ?? $name,
                'menu_item_id' => $menuId,
                'qty' => 0,
                'price' => $price
            ];
        }
        $groupedItems[$key]['qty'] += $qty;
        $combinedSubtotal += ($price * $qty);
    }
    $items = array_values($groupedItems);

    $gstRate = 0.05;
    $combinedGst = round($combinedSubtotal * $gstRate, 2);
    $combinedTotal = round($combinedSubtotal + $combinedGst, 2);

    $notes = implode('; ', array_filter(array_column($orders, 'customer_notes')));

    jsonResponse([
        'success'    => true,
        'exists'     => true,
        'order_id'   => (int)$primaryOrder['id'],
        'order_ref'  => $primaryOrder['order_ref'],
        'persons'    => (int)$primaryOrder['persons'],
        'created_at' => $primaryOrder['created_at'],
        'subtotal'   => $combinedSubtotal,
        'gst'        => $combinedGst,
        'total'      => $combinedTotal,
        'notes'      => $notes,
        'items'      => $items
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Failed to fetch active bill.', 'detail' => $e->getMessage()], 500);
}

