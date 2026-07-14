<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * get_stats.php — Golden Stone Hotel
 * GET: Returns today's order statistics for Kitchen Dashboard.
 * ═══════════════════════════════════════════════════════════════
 *
 * Polled by kitchen.js alongside get_orders.php
 *
 * Response format (must match kitchen.js field names):
 * {
 *   "success": true,
 *   "total_today": 120,
 *   "active": 15,
 *   "new": 3,
 *   "preparing": 8,
 *   "ready": 2,
 *   "served": 107
 * }
 *
 * kitchen.js reads:
 *   data.total_today  → #statTotal
 *   data.active       → #statActive
 *   data.new          → #statNew
 *   data.preparing    → #statPreparing
 *   data.ready        → #statReady
 *   data.served       → #statServed
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

// ─── Only GET allowed ───
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed. Use GET.'], 405);
}

$pdo   = getDB();
$today = date('Y-m-d');

try {
    // Single optimized query using conditional aggregation
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*)                                              AS total_today,
            SUM(CASE WHEN status != 'served'    THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN status = 'new'        THEN 1 ELSE 0 END) AS `new`,
            SUM(CASE WHEN status = 'preparing'  THEN 1 ELSE 0 END) AS preparing,
            SUM(CASE WHEN status = 'ready'      THEN 1 ELSE 0 END) AS ready,
            SUM(CASE WHEN status = 'served'     THEN 1 ELSE 0 END) AS served,
            SUM(CASE WHEN status = 'served'     THEN total_amount ELSE 0.00 END) AS revenue
        FROM orders
        WHERE DATE(created_at) = :today
    ");
    $stmt->execute(['today' => $today]);
    $stats = $stmt->fetch();

    // ─── Response — field names match kitchen.js exactly ───
    jsonResponse([
        'success'     => true,
        'date'        => $today,
        'total_today' => (int)($stats['total_today'] ?? 0),
        'active'      => (int)($stats['active']      ?? 0),
        'new'         => (int)($stats['new']          ?? 0),
        'preparing'   => (int)($stats['preparing']    ?? 0),
        'ready'       => (int)($stats['ready']        ?? 0),
        'served'      => (int)($stats['served']       ?? 0),
        'revenue'     => (float)($stats['revenue']     ?? 0.00),
    ]);

} catch (PDOException $e) {
    jsonResponse([
        'success' => false,
        'error'   => 'Failed to fetch statistics.',
        'detail'  => $e->getMessage(),
    ], 500);
}
