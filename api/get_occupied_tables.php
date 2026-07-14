<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$pdo = getDB();

try {
    // 1. Get all table numbers that are either in occupied_tables OR have an active order (status != 'served')
    $tablesQuery = $pdo->query("
        SELECT DISTINCT CAST(table_no AS UNSIGNED) AS t_no FROM occupied_tables
        UNION
        SELECT DISTINCT CAST(table_no AS UNSIGNED) AS t_no FROM orders WHERE status != 'served'
    ");
    $activeTables = $tablesQuery->fetchAll(PDO::FETCH_COLUMN);

    $occupiedList = [];
    if (!empty($activeTables)) {
        foreach ($activeTables as $tNo) {
            // Find active order for this table
            $orderStmt = $pdo->prepare("
                SELECT id, total_amount, created_at
                FROM orders
                WHERE table_no = ? AND status != 'served'
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $orderStmt->execute([(string)$tNo]);
            $activeOrder = $orderStmt->fetch();

            // Find occupation record
            $occStmt = $pdo->prepare("SELECT occupied_at, persons FROM occupied_tables WHERE table_no = ?");
            $occStmt->execute([$tNo]);
            $occRecord = $occStmt->fetch();

            $itemsCount = 0;
            $amount = 0.0;
            $minutesAgo = 0;

            if ($activeOrder) {
                $orderId = $activeOrder['id'];
                // Count total item quantity in active order
                $itemsStmt = $pdo->prepare("SELECT SUM(qty) FROM order_items WHERE order_id = ?");
                $itemsStmt->execute([$orderId]);
                $itemsCount = (int)$itemsStmt->fetchColumn();
                $amount = floatval($activeOrder['total_amount']);
                
                $createdTime = strtotime($activeOrder['created_at']);
                $minutesAgo = (int)floor((time() - $createdTime) / 60);
            }

            if ($occRecord) {
                $occTime = strtotime($occRecord['occupied_at']);
                $occMinutes = (int)floor((time() - $occTime) / 60);
                // If occupied time is longer ago than the order creation, use it
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
