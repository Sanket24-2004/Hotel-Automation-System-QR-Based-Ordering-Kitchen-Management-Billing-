<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * get_orders.php — Golden Stone Hotel
 * GET: Returns active orders for the Kitchen Dashboard.
 * ═══════════════════════════════════════════════════════════════
 *
 * Polled every 3 seconds by kitchen.js
 *
 * Query params:
 *   ?since=<unix_timestamp>  — only orders updated after this time
 *   ?status=all|new|preparing|ready|served
 *
 * Response format matches kitchen.js expectations exactly:
 * {
 *   "success": true,
 *   "timestamp": 1717400000,
 *   "count": 5,
 *   "orders": [
 *     {
 *       "id": 1,
 *       "order_ref": "ORD-260603-T5-001",
 *       "table_no": "5",
 *       "persons": 3,
 *       "status": "new",
 *       "customer_note": "Less Spicy",
 *       "created_at": "2026-06-03 19:25:00",
 *       "updated_at": "2026-06-03 19:30:00",
 *       "prep_started_at": null,
 *       "ready_at": null,
 *       "served_at": null,
 *       "batches": [
 *         {
 *           "batch_id": "B1717400000_a1b2c3d4",
 *           "added_at": "2026-06-03 19:25:00",
 *           "items": [
 *             { "id": 5, "name": "Paneer Tikka", "category": "starter", "price": 220, "qty": 2, "is_new": 1 }
 *           ]
 *         }
 *       ],
 *       "status_log": [
 *         { "status": "new", "note": "Order received", "changed_at": "2026-06-03 19:25:00" }
 *       ]
 *     }
 *   ]
 * }
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

// ─── Only GET allowed ───
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed. Use GET.'], 405);
}

$pdo = getDB();

// ─── Parse query parameters ───
$sinceTimestamp = intval($_GET['since'] ?? 0);
$statusFilter  = trim((string)($_GET['status'] ?? 'all'));
$today         = date('Y-m-d');

$validStatuses = ['new', 'preparing', 'ready', 'served'];
if ($statusFilter !== 'all' && !in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = 'all';
}

// Category mapping: database ENUM → kitchen.js short names
// kitchen.js uses: starter, main, bread, rice, beverage, dessert, salad, side, water
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
    // ════════════════════════════════════════════
    // STEP 1: Fetch today's orders
    // ════════════════════════════════════════════

    $sinceDate = $sinceTimestamp > 0
        ? date('Y-m-d H:i:s', $sinceTimestamp)
        : '1970-01-01 00:00:00';

    if ($statusFilter === 'all') {
        $orderSql = "
            SELECT *
            FROM orders
            WHERE DATE(created_at) = :today
               OR updated_at > :since
            ORDER BY
                CASE status
                    WHEN 'new'       THEN 1
                    WHEN 'preparing' THEN 2
                    WHEN 'ready'     THEN 3
                    WHEN 'served'    THEN 4
                END,
                created_at ASC
        ";
        $orderStmt = $pdo->prepare($orderSql);
        $orderStmt->execute([
            'today' => $today,
            'since' => $sinceDate,
        ]);
    } else {
        $orderSql = "
            SELECT *
            FROM orders
            WHERE DATE(created_at) = :today
              AND status = :status
            ORDER BY created_at ASC
        ";
        $orderStmt = $pdo->prepare($orderSql);
        $orderStmt->execute([
            'today'  => $today,
            'status' => $statusFilter,
        ]);
    }

    $orders   = $orderStmt->fetchAll();
    $orderIds = array_column($orders, 'id');

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

            // Map category to kitchen.js short name
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
    // STEP 3: Fetch status history for all orders
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

        // Get batches for this order (as indexed array)
        $batches = isset($batchedItems[$oid])
            ? array_values($batchedItems[$oid])
            : [];

        // Get status log for this order
        $logs = $statusLogs[$oid] ?? [];

        $result[] = [
            // Core fields — kitchen.js field names
            'id'              => $oid,
            'order_ref'       => $order['order_ref'],
            'table_no'        => $order['table_no'],
            'persons'         => (int)$order['persons'],
            'status'          => $order['status'],

            // Note: kitchen.js reads "customer_note" (singular)
            // Database stores "customer_notes" (plural)
            'customer_note'   => $order['customer_notes'] ?? '',

            // Financials
            'subtotal'        => (float)$order['subtotal'],
            'gst_amount'      => (float)$order['gst_amount'],
            'total_amount'    => (float)$order['total_amount'],

            // Timestamps
            'prep_started_at' => $order['prep_started_at'],
            'ready_at'        => $order['ready_at'],
            'served_at'       => $order['served_at'],
            'created_at'      => $order['created_at'],
            'updated_at'      => $order['updated_at'],

            // Nested data
            'batches'         => $batches,
            'status_log'      => $logs,
        ];
    }

    // ─── Return response ───
    jsonResponse([
        'success'   => true,
        'timestamp' => time(),
        'count'     => count($result),
        'orders'    => $result,
    ]);

} catch (PDOException $e) {
    jsonResponse([
        'success' => false,
        'error'   => 'Failed to fetch orders.',
        'detail'  => $e->getMessage(),
    ], 500);

} catch (\Exception $e) {
    jsonResponse([
        'success' => false,
        'error'   => 'Unexpected error.',
        'detail'  => $e->getMessage(),
    ], 500);
}
