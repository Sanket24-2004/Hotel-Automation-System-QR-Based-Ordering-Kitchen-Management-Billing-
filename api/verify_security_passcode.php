<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed. Use POST.'], 405);
}

$body = getJsonBody();
$passcode = trim((string)($body['passcode'] ?? ''));

if ($passcode === '') {
    jsonResponse(['success' => false, 'error' => 'Please enter the table passcode.'], 400);
}

$pdo = getDB();
try {
    $stmt = $pdo->query("SELECT daily_passcode FROM hotel_settings WHERE id = 1 LIMIT 1");
    $stored = $stmt->fetchColumn();

    if (!$stored) {
        $stored = '1234';
    }

    if (strcasecmp($passcode, (string)$stored) === 0) {
        // Generate temporary auth token
        $token = hash('sha256', $passcode . date('Y-m-d') . 'hotel_tulsi_secret');
        jsonResponse([
            'success' => true,
            'token'   => $token,
            'message' => 'Passcode verified successfully.'
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'error'   => 'Incorrect passcode. Please ask your waiter or check your table card.'
        ], 401);
    }
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
}
