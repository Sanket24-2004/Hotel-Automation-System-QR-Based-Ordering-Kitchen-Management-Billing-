<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * get_history.php — Golden Stone Hotel
 * GET: Returns served orders from the last 24 hours (FIFO/Latest first)
 * ═══════════════════════════════════════════════════════════════
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed. Use GET.'], 405);
}

$pdo = getDB();

$categoryDbToJs = [
    'starter'      => 'starter',
    'main_course'  => 'main',
    'bread'        => 'bread',
    'rice_biryani' => 'rice',
    'beverage'     => 'beverage',
    'dessert'      => 'dessert',
    'salad'        => 'salad',
    'side_dish'    => 'side',
    'water'        => 'water',
];

try {
    $days = isset($_GET['days']) ? (string)$_GET['days'] : 'all';
    
    $whereClause = "WHERE payment_method IS NOT NULL";
    $queryParams = [];
    
    if ($days !== 'all' && $days !== '0' && $days !== '') {
        $daysCount = intval($days);
        if ($daysCount > 0) {
            $whereClause .= " AND created_at >= NOW() - INTERVAL ? DAY";
            $queryParams[] = $daysCount;
        }
    }

    // ════════════════════════════════════════════
    // STEP 1: Fetch served orders
    // ════════════════════════════════════════════
    $orderSql = "
        SELECT *
        FROM orders
        $whereClause
        ORDER BY created_at DESC
    ";
    $orderStmt = $pdo->prepare($orderSql);
    $orderStmt->execute($queryParams);
    $orders    = $orderStmt->fetchAll();
    $orderIds  = array_column($orders, 'id');

    // ════════════════════════════════════════════
    // STEP 2: Fetch items for all orders (batched)
    // ════════════════════════════════════════════
    $batchedItems = [];
    if (!empty($orderIds)) {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $itemStmt = $pdo->prepare("
            SELECT *
            FROM order_items
            WHERE order_id IN ($placeholders)
            ORDER BY added_at ASC, id ASC
        ");
        $itemStmt->execute($orderIds);

        foreach ($itemStmt->fetchAll() as $item) {
            $oid = (int)$item['order_id'];
            $bid = $item['batch_id'];

            if (!isset($batchedItems[$oid][$bid])) {
                $batchedItems[$oid][$bid] = [
                    'batch_id' => $bid,
                    'added_at' => $item['added_at'],
                    'items'    => [],
                ];
            }

            $dbCategory = $item['category'];
            $jsCategory = $categoryDbToJs[$dbCategory] ?? $dbCategory;

            $batchedItems[$oid][$bid]['items'][] = [
                'id'       => (int)$item['menu_item_id'],
                'name'     => $item['item_name'],
                'category' => $jsCategory,
                'price'    => (float)$item['unit_price'],
                'qty'      => (int)$item['qty'],
                'is_new'   => (int)$item['is_new'],
            ];
        }
    }

    // ════════════════════════════════════════════
    // STEP 3: Fetch status history
    // ════════════════════════════════════════════
    $statusLogs = [];
    if (!empty($orderIds)) {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $logStmt = $pdo->prepare("
            SELECT *
            FROM order_status_history
            WHERE order_id IN ($placeholders)
            ORDER BY changed_at ASC
        ");
        $logStmt->execute($orderIds);

        foreach ($logStmt->fetchAll() as $log) {
            $oid = (int)$log['order_id'];
            $statusLogs[$oid][] = [
                'status'     => $log['status'],
                'note'       => $log['note'],
                'changed_at' => $log['changed_at'],
            ];
        }
    }

    // ════════════════════════════════════════════
    // STEP 4: Assemble response
    // ════════════════════════════════════════════
    $result = [];
    foreach ($orders as $order) {
        $oid = (int)$order['id'];
        $batches = isset($batchedItems[$oid]) ? array_values($batchedItems[$oid]) : [];
        $logs = $statusLogs[$oid] ?? [];

        $result[] = [
            'id'              => $oid,
            'order_ref'       => $order['order_ref'],
            'table_no'        => $order['table_no'],
            'persons'         => (int)$order['persons'],
            'status'          => $order['status'],
            'payment_method'  => $order['payment_method'] ?? null,
            'discount_amount' => (float)($order['discount_amount'] ?? 0),
            'discount_type'   => $order['discount_type'] ?? null,
            'customer_note'   => $order['customer_notes'] ?? '',
            'subtotal'        => (float)$order['subtotal'],
            'gst_amount'      => (float)$order['gst_amount'],
            'total_amount'    => (float)$order['total_amount'],
            'prep_started_at' => $order['prep_started_at'],
            'ready_at'        => $order['ready_at'],
            'served_at'       => $order['served_at'],
            'created_at'      => $order['created_at'],
            'updated_at'      => $order['updated_at'],
            'batches'         => $batches,
            'status_log'      => $logs,
        ];
    }

    jsonResponse([
        'success'   => true,
        'timestamp' => time(),
        'count'     => count($result),
        'orders'    => $result,
    ]);

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Failed to fetch history.', 'detail' => $e->getMessage()], 500);
} catch (\Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Unexpected error.', 'detail' => $e->getMessage()], 500);
}
