<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * db.php — Golden Stone Hotel
 * PDO Database Connection (PHP 8.0+)
 * ═══════════════════════════════════════════════════════════════
 *
 * Usage:
 *   require_once __DIR__ . '/db.php';
 *   $pdo = getDB();
 *
 * All API endpoints include this file for database access.
 * Uses a static singleton so the connection is reused within a request.
 */

declare(strict_types=1);

require_once __DIR__ . '/table_token.php';

// ─── Set Default Timezone ───
// Ensures all date() and time() calls match the local Indian timezone (IST)
date_default_timezone_set('Asia/Kolkata');

// ─── Database Credentials ───
// Adjust these if your XAMPP uses a different port or password.
define('DB_HOST',    'localhost');
define('DB_PORT',    3306);
define('DB_NAME',    'golden_stone_hotel');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO connection to the golden_stone_hotel database.
 * Throws a clean JSON error response if the connection fails.
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $initCmd = defined('PDO::MYSQL_ATTR_INIT_COMMAND') ? PDO::MYSQL_ATTR_INIT_COMMAND : 1002;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        $initCmd                     => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Return a clean JSON error — no stack trace exposed in production
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'error'   => 'Database connection failed.',
            'hint'    => 'Ensure XAMPP Apache + MySQL are running. Check credentials in api/db.php.',
            'detail'  => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $pdo;
}

// ─── CORS Headers ───
// Allow cross-origin requests from any device on the LAN.
if (!headers_sent()) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (!headers_sent()) {
        http_response_code(204);
    }
    exit;
}

// Default response type for all API endpoints
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

/**
 * Helper: Send a JSON response and exit.
 *
 * @param array $data     Response payload
 * @param int   $httpCode HTTP status code (default 200)
 */
function jsonResponse(array $data, int $httpCode = 200): never
{
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Helper: Read and decode JSON from the request body.
 *
 * @return array Decoded JSON as associative array
 */
function getJsonBody(): array
{
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        jsonResponse([
            'success' => false,
            'error'   => 'Invalid or missing JSON body.',
        ], 400);
    }

    return $data;
}

/**
 * Helper: Validate that required fields exist in the data array.
 *
 * @param array  $data     Input data
 * @param array  $required List of required field names
 * @param string $context  Context label for error message
 */
function requireFields(array $data, array $required, string $context = 'Request'): void
{
    $missing = [];
    foreach ($required as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        jsonResponse([
            'success' => false,
            'error'   => "$context is missing required fields: " . implode(', ', $missing),
        ], 400);
    }
}
