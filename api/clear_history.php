<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed. Use POST.'], 405);
}

$pdo = getDB();
try {
    $pdo->exec("DELETE FROM orders WHERE status = 'served'");
    jsonResponse(['success' => true, 'message' => 'Payment history cleared successfully.']);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Failed to clear payment history.', 'detail' => $e->getMessage()], 500);
}
