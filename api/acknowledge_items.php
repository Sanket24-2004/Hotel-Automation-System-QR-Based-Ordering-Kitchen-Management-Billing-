<?php
/**
 * acknowledge_items.php — Golden Stone Hotel
 * POST: Clears is_new=1 flags after kitchen dashboard has displayed them.
 * Called automatically by kitchen.html 8 seconds after showing NEW ITEM badge.
 */

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

if (!$body || !isset($body['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'order_id required']);
    exit;
}

$orderId  = intval($body['order_id']);
$batchId  = $body['batch_id'] ?? null;

$pdo = getDB();

try {
    if ($batchId) {
        // Acknowledge a specific batch
        $stmt = $pdo->prepare("UPDATE order_items SET is_new = 0 WHERE order_id = ? AND batch_id = ? AND is_new = 1");
        $stmt->execute([$orderId, $batchId]);
    } else {
        // Acknowledge all items for this order
        $stmt = $pdo->prepare("UPDATE order_items SET is_new = 0 WHERE order_id = ? AND is_new = 1");
        $stmt->execute([$orderId]);
    }

    echo json_encode(['success' => true, 'rows_cleared' => $stmt->rowCount()]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
