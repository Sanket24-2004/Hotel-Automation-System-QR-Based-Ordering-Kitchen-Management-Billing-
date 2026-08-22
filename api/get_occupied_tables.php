<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$pdo = getDB();

try {
    // 1. Get all table numbers that are either in occupied_tables OR have an unbilled order (payment_method IS NULL)
    $tablesQuery = $pdo->query("
        SELECT DISTINCT CAST(table_no AS UNSIGNED) AS t_no FROM occupied_tables
        UNION
        SELECT DISTINCT CAST(table_no AS UNSIGNED) AS t_no FROM orders WHERE payment_method IS NULL
    ");
    $activeTables = $tablesQuery->fetchAll(PDO::FETCH_COLUMN);

    $occupiedList = [];
    if (!empty($activeTables)) {
        foreach ($activeTables as $tNo) {
            // Find active unbilled orders for this table
            $orderStmt = $pdo->prepare("
                SELECT id, total_amount, created_at
                FROM orders
                WHERE table_no = ? AND payment_method IS NULL
                ORDER BY created_at DESC
            ");
            $orderStmt->execute([(string)$tNo]);
            $activeOrders = $orderStmt->fetchAll();

            // Find occupation record
            $occStmt = $pdo->prepare("SELECT occupied_at, persons FROM occupied_tables WHERE table_no = ?");
            $occStmt->execute([$tNo]);
            $occRecord = $occStmt->fetch();

            $itemsCount = 0;
            $amount = 0.0;
            $minutesAgo = 0;

            if (!empty($activeOrders)) {
                $orderIds = array_column($activeOrders, 'id');
                $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
                
                // Count total item quantity in active orders
                $itemsStmt = $pdo->prepare("SELECT SUM(qty) FROM order_items WHERE order_id IN ($placeholders)");
                $itemsStmt->execute($orderIds);
                $itemsCount = (int)$itemsStmt->fetchColumn();
                
                // Calculate combined total amount
                $subtotalStmt = $pdo->prepare("SELECT SUM(line_total) FROM order_items WHERE order_id IN ($placeholders)");
                $subtotalStmt->execute($orderIds);
                $combinedSubtotal = (float)$subtotalStmt->fetchColumn();
                $amount = round($combinedSubtotal * 1.05, 2);
                
                $createdTime = strtotime($activeOrders[0]['created_at']);
                $minutesAgo = (int)floor((time() - $createdTime) / 60);
            }

            if ($occRecord) {
                $occTime = strtotime($occRecord['occupied_at']);
                $occMinutes = (int)floor((time() - $occTime) / 60);
                if ($occMinutes > $minutesAgo) {
                    $minutesAgo = $occMinutes;
                }
            }

            $occupiedList[] = [
                'id' => (int)$tNo,
                'items' => $itemsCount,
                'amount' => $amount,
                'minutesAgo' => max(0, $minutesAgo)
            ];
        }
    }

    // Sort by table number ascending
    usort($occupiedList, function($a, $b) {
        return $a['id'] <=> $b['id'];
    });

    jsonResponse(['success' => true, 'tables' => $occupiedList]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Failed to fetch occupied tables.', 'detail' => $e->getMessage()], 500);
}
