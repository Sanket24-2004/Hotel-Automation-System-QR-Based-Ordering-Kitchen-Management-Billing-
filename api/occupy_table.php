<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Accept parameters from POST (JSON) or GET
if ($method === 'POST') {
    $body = getJsonBody();
    $action = $body['action'] ?? 'occupy';
    $table = (int)($body['table'] ?? 0);
    $persons = (int)($body['persons'] ?? 1);
} else {
    $action = $_GET['action'] ?? 'occupy';
    $table = (int)($_GET['table'] ?? 0);
    $persons = (int)($_GET['persons'] ?? 1);
}

if ($table <= 0) {
    jsonResponse(['success' => false, 'error' => 'Invalid or missing table number.'], 400);
}

if ($action === 'occupy') {
    $stmt = $pdo->prepare("
        INSERT INTO occupied_tables (table_no, persons, occupied_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE persons = ?, occupied_at = NOW()
    ");
    $stmt->execute([$table, $persons, $persons]);
    jsonResponse(['success' => true, 'message' => "Table $table marked as occupied."]);
} elseif ($action === 'release') {
    $stmt = $pdo->prepare("DELETE FROM occupied_tables WHERE table_no = ?");
    $stmt->execute([$table]);
    jsonResponse(['success' => true, 'message' => "Table $table released."]);
} else {
    jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
}
