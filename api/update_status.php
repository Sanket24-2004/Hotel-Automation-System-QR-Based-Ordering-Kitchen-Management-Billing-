<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * update_status.php — Golden Stone Hotel
 * POST: Kitchen staff updates order status through the dashboard.
 * ═══════════════════════════════════════════════════════════════
 *
 * Status Flow (strict one-way transitions):
 *   new → preparing → ready → served
 *
 * Request body (JSON):
 * {
 *   "order_id": 101,
 *   "status": "preparing"
 * }
 *
 * Response:
 * {
 *   "success": true,
 *   "updated_at": "2026-06-03 19:28:00",
 *   "message": "Order status updated to preparing"
 * }
 *
 * kitchen.js reads: data.success, data.updated_at
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

// ─── Only POST allowed ───
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed. Use POST.'], 405);
}

// ─── Parse and validate request ───
$body = getJsonBody();

if (!isset($body['order_id']) || !isset($body['status'])) {
    jsonResponse([
        'success' => false,
        'error'   => 'Both order_id and status are required.',
    ], 400);
}

$orderId   = intval($body['order_id']);
$newStatus = trim((string)$body['status']);
$note      = trim((string)($body['note'] ?? ''));

if ($orderId < 1) {
    jsonResponse(['success' => false, 'error' => 'Invalid order_id.'], 400);
}

// ─── Validate status value ───
$validStatuses = ['new', 'preparing', 'ready', 'served'];
if (!in_array($newStatus, $validStatuses, true)) {
    jsonResponse([
        'success' => false,
        'error'   => "Invalid status '$newStatus'. Allowed: " . implode(', ', $validStatuses),
    ], 400);
}

// ─── Allowed transitions (strict one-way) ───
$allowedTransitions = [
    'new'       => 'preparing',
    'preparing' => 'ready',
    'ready'     => 'served',
];

$pdo = getDB();

try {
    // ─── Fetch current order ───
    $stmt = $pdo->prepare("
        SELECT id, order_ref, table_no, persons, status,
               total_amount, gst_amount
        FROM orders
        WHERE id = :id
    ");
    $stmt->execute(['id' => $orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonResponse(['success' => false, 'error' => 'Order not found.'], 404);
    }

    $currentStatus = $order['status'];

    // ─── Validate transition ───
    if ($currentStatus === $newStatus) {
        jsonResponse([
            'success' => false,
            'error'   => "Order is already in '$currentStatus' status.",
        ], 400);
    }

    if ($currentStatus === 'served') {
        jsonResponse([
            'success' => false,
            'error'   => 'Cannot change status of a served order.',
        ], 400);
    }

    $expectedNext = $allowedTransitions[$currentStatus] ?? null;
    if ($expectedNext !== $newStatus) {
        jsonResponse([
            'success' => false,
            'error'   => "Invalid transition: '$currentStatus' → '$newStatus'. Expected: '$currentStatus' → '$expectedNext'.",
        ], 400);
    }

    // ════════════════════════════════════════════
    // UPDATE ORDER STATUS
    // ════════════════════════════════════════════

    $now = date('Y-m-d H:i:s');

    $pdo->beginTransaction();

    // ─── Build status-specific timestamp update ───
    $timestampField = '';
    $timestampParam = [];

    switch ($newStatus) {
        case 'preparing':
            $timestampField = ', prep_started_at = :ts';
            $timestampParam = ['ts' => $now];
            if ($note === '') $note = 'Preparation started';
            break;

        case 'ready':
            $timestampField = ', ready_at = :ts';
            $timestampParam = ['ts' => $now];
            if ($note === '') $note = 'Ready to serve';
            break;

        case 'served':
            $timestampField = ', served_at = :ts';
            $timestampParam = ['ts' => $now];
            if ($note === '') $note = 'Served to customer';
            break;
    }

    // ─── Update orders table ───
    $updateSql = "
        UPDATE orders
        SET status     = :status,
            updated_at = :now
            $timestampField
        WHERE id = :id
    ";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute(array_merge([
        'status' => $newStatus,
        'now'    => $now,
        'id'     => $orderId,
    ], $timestampParam));

    // ─── Log to order_status_history ───
    $logStmt = $pdo->prepare("
        INSERT INTO order_status_history (order_id, status, note, changed_at)
        VALUES (:order_id, :status, :note, :now)
    ");
    $logStmt->execute([
        'order_id' => $orderId,
        'status'   => $newStatus,
        'note'     => $note,
        'now'      => $now,
    ]);

    // ─── If status = ready, clear is_new flags on all items ───
    if ($newStatus === 'ready') {
        $pdo->prepare("
            UPDATE order_items
            SET is_new = 0
            WHERE order_id = :order_id AND is_new = 1
        ")->execute(['order_id' => $orderId]);
    }

    $pdo->commit();

    // ─── Success response ───
    jsonResponse([
        'success'    => true,
        'order_id'   => $orderId,
        'old_status' => $currentStatus,
        'new_status' => $newStatus,
        'updated_at' => $now,
        'message'    => "Order status updated to $newStatus",
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse([
        'success' => false,
        'error'   => 'Failed to update order status.',
        'detail'  => $e->getMessage(),
    ], 500);

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse([
        'success' => false,
        'error'   => 'Unexpected error.',
        'detail'  => $e->getMessage(),
    ], 500);
}
