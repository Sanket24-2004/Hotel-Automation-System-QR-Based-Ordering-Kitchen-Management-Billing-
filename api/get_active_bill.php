<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$pdo = getDB();
$table = trim($_GET['table'] ?? '');

if ($table === '') {
    jsonResponse(['success' => false, 'error' => 'Missing table number.'], 400);
}

try {
    // Find active order for this table
    $stmt = $pdo->prepare("
        SELECT id, order_ref, table_no, persons, subtotal, gst_amount, total_amount, customer_notes, created_at
        FROM orders
        WHERE table_no = ? AND status != 'served'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$table]);
    $order = $stmt->fetch();

    if (!$order) {
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

    // Fetch items
    $itemStmt = $pdo->prepare("
        SELECT item_name AS name, qty, unit_price AS price
        FROM order_items
        WHERE order_id = ?
        ORDER BY added_at ASC
    ");
    $itemStmt->execute([$order['id']]);
    $items = $itemStmt->fetchAll();

    jsonResponse([
        'success'  => true,
        'exists'   => true,
        'order_id' => (int)$order['id'],
        'order_ref'=> $order['order_ref'],
        'persons'  => (int)$order['persons'],
        'subtotal' => floatval($order['subtotal']),
        'gst'      => floatval($order['gst_amount']),
        'total'    => floatval($order['total_amount']),
        'notes'    => $order['customer_notes'] ?? '',
        'items'    => $items
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Failed to fetch active bill.', 'detail' => $e->getMessage()], 500);
}
