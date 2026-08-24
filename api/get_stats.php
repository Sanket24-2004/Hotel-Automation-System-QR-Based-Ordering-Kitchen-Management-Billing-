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
    // 1. Revenue today & completed orders count today (payment completed)
    $revStmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(total_amount), 0.00) AS revenue,
            COUNT(*)                          AS completed_count
        FROM orders
        WHERE payment_method IS NOT NULL
          AND DATE(served_at) = ?
    ");
    $revStmt->execute([$today]);
    $revStats = $revStmt->fetch();

    $revenue = (float)($revStats['revenue'] ?? 0.00);
    $completedCount = (int)($revStats['completed_count'] ?? 0);

    // 2. Active unbilled orders
    $activeStmt = $pdo->query("
        SELECT COUNT(*) FROM orders WHERE payment_method IS NULL
    ");
    $activeCount = (int)$activeStmt->fetchColumn();

    // 3. Total orders today = Active + Completed today
    $totalToday = $activeCount + $completedCount;

    jsonResponse([
        'success'     => true,
        'date'        => $today,
        'total_today' => $totalToday,
        'active'      => $activeCount,
        'completed'   => $completedCount,
        'revenue'     => $revenue,
    ]);

} catch (PDOException $e) {
    jsonResponse([
        'success' => false,
        'error'   => 'Failed to fetch statistics.',
        'detail'  => $e->getMessage(),
    ], 500);
}
