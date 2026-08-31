<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * update_status.php — Golden Stone Hotel
 * POST: Kitchen staff updates order / item status through the dashboard.
 * ═══════════════════════════════════════════════════════════════
 *
 * Supported Actions:
 * 1. Update whole order:
 *    { "order_id": 101, "status": "served" | "preparing" | "ready" | "new" }
 * 2. Toggle whole order served:
 *    { "order_id": 101, "action": "toggle_served" }
 * 3. Update / Toggle specific item:
 *    { "order_id": 101, "item_id": 505, "action": "toggle_item_served" | "set_item_served", "is_served": 1|0 }
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed. Use POST.'], 405);
}

$body = getJsonBody();
$pdo  = getDB();

$orderId = intval($body['order_id'] ?? 0);
$itemId  = intval($body['item_id'] ?? 0);
$action  = trim((string)($body['action'] ?? ''));
$status  = trim((string)($body['status'] ?? ''));
$note    = trim((string)($body['note'] ?? ''));

if ($orderId < 1) {
    jsonResponse(['success' => false, 'error' => 'Valid order_id is required.'], 400);
}

$now = date('Y-m-d H:i:s');

try {
    // ─── Fetch current order ───
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
    $stmt->execute(['id' => $orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonResponse(['success' => false, 'error' => 'Order not found.'], 404);
    }

    $pdo->beginTransaction();

    // ─── ACTION 1: Toggle or Set Specific Item Served State ───
    if ($itemId > 0 || $action === 'toggle_item_served' || $action === 'set_item_served') {
        $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE id = :item_id AND order_id = :order_id");
        $itemStmt->execute(['item_id' => $itemId, 'order_id' => $orderId]);
        $orderItem = $itemStmt->fetch();

        if (!$orderItem) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'error' => 'Order item not found.'], 404);
        }

        $newServed = isset($body['is_served']) 
            ? (int)(bool)$body['is_served'] 
            : ($orderItem['is_served'] ? 0 : 1);

        $updateItemStmt = $pdo->prepare("
            UPDATE order_items 
            SET is_served = :is_served, 
                served_at = :served_at,
                is_new = 0
            WHERE id = :item_id
        ");
        $updateItemStmt->execute([
            'is_served' => $newServed,
            'served_at' => $newServed ? $now : null,
            'item_id'   => $itemId
        ]);

        // Check overall items status in this order
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(is_served) as served_count FROM order_items WHERE order_id = :order_id");
        $checkStmt->execute(['order_id' => $orderId]);
        $counts = $checkStmt->fetch();

        $totalItems = (int)($counts['total'] ?? 0);
        $servedItems = (int)($counts['served_count'] ?? 0);

        if ($totalItems > 0 && $servedItems >= $totalItems) {
            $orderStatus = 'served';
            $servedAt = $now;
        } else {
            $orderStatus = 'preparing';
            $servedAt = null;
        }

        $pdo->prepare("
            UPDATE orders 
            SET status = :status, 
                served_at = :served_at,
                updated_at = :now
            WHERE id = :id
        ")->execute([
            'status'    => $orderStatus,
            'served_at' => $servedAt,
            'now'       => $now,
            'id'        => $orderId
        ]);

        $pdo->commit();

        jsonResponse([
            'success'      => true,
            'order_id'     => $orderId,
            'item_id'      => $itemId,
            'is_served'    => (bool)$newServed,
            'order_status' => $orderStatus,
            'all_served'   => ($totalItems > 0 && $servedItems >= $totalItems),
            'updated_at'   => $now
        ]);
    }

    // ─── ACTION 2: Toggle whole order served state ───
    if ($action === 'toggle_served') {
        if ($order['status'] === 'served') {
            $newStatus = 'preparing';
            $newServed = 0;
            $servedAt  = null;
            if ($note === '') $note = 'Order reopened / unserved';
        } else {
            $newStatus = 'served';
            $newServed = 1;
            $servedAt  = $now;
            if ($note === '') $note = 'Whole order marked served';
        }
    } else {
        // Direct status setting
        $newStatus = $status !== '' ? $status : 'served';
        $newServed = ($newStatus === 'served') ? 1 : 0;
        $servedAt  = ($newStatus === 'served') ? $now : null;
    }

    // Update all items in this order
    $pdo->prepare("
        UPDATE order_items
        SET is_served = :is_served,
            served_at = :served_at,
            is_new = 0
        WHERE order_id = :order_id
    ")->execute([
        'is_served' => $newServed,
        'served_at' => $servedAt,
        'order_id'  => $orderId
    ]);

    // Update orders table
    $updateSql = "
        UPDATE orders
        SET status     = :status,
            updated_at = :now,
            served_at  = :served_at
        WHERE id = :id
    ";
    $pdo->prepare($updateSql)->execute([
        'status'    => $newStatus,
        'now'       => $now,
        'served_at' => $servedAt,
        'id'        => $orderId
    ]);

    // Log to order_status_history
    $pdo->prepare("
        INSERT INTO order_status_history (order_id, status, note, changed_at)
        VALUES (:order_id, :status, :note, :now)
    ")->execute([
        'order_id' => $orderId,
        'status'   => $newStatus,
        'note'     => $note !== '' ? $note : "Status updated to $newStatus",
        'now'      => $now
    ]);

    $pdo->commit();

    jsonResponse([
        'success'      => true,
        'order_id'     => $orderId,
        'order_status' => $newStatus,
        'is_served'    => ($newStatus === 'served'),
        'all_served'   => ($newStatus === 'served'),
        'updated_at'   => $now,
        'message'      => "Order status updated to $newStatus"
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
}
