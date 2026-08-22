<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$pdo = getDB();
$table = trim($_GET['table'] ?? '');

if ($table === '') {
    jsonResponse(['success' => false, 'error' => 'Missing table number.'], 400);
}

try {
    // Find active unbilled orders for this table
    $stmt = $pdo->prepare("
        SELECT id, order_ref, table_no, persons, subtotal, gst_amount, total_amount, customer_notes, created_at
        FROM orders
        WHERE table_no = ? AND payment_method IS NULL
        ORDER BY created_at DESC
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
            'items' => []
        ]);
    }

    $primaryOrder = $orders[0];
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

    // Fetch items for all unbilled orders on this table
    $itemStmt = $pdo->prepare("
        SELECT item_name AS name, qty, unit_price AS price
        FROM order_items
        WHERE order_id IN ($placeholders)
        ORDER BY added_at ASC
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

        $key = $name . '_' . $price;
        if (!isset($groupedItems[$key])) {
            $groupedItems[$key] = [
                'name' => $name,
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
        'success'  => true,
        'exists'   => true,
        'order_id' => (int)$primaryOrder['id'],
        'order_ref'=> $primaryOrder['order_ref'],
        'persons'  => (int)$primaryOrder['persons'],
        'subtotal' => $combinedSubtotal,
        'gst'      => $combinedGst,
        'total'    => $combinedTotal,
        'notes'    => $notes,
        'items'    => $items
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Failed to fetch active bill.', 'detail' => $e->getMessage()], 500);
}
