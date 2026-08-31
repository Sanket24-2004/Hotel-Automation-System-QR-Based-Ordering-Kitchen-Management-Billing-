<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * table_token.php — Table Security Token Generator & Validator
 * Option A: QR-Based Table Security Signature
 * ═══════════════════════════════════════════════════════════════
 */

declare(strict_types=1);

define('TABLE_TOKEN_SECRET', 'HotelTulsi_Table_Token_Secret_2026_');

/**
 * Generates the deterministic security token for a given table number.
 */
function getTableSecurityToken(string|int $tableNo): string {
    return substr(md5(TABLE_TOKEN_SECRET . trim((string)$tableNo)), 0, 10);
}

/**
 * Validates whether the supplied token matches the table's secret signature.
 * Returns true if valid, false otherwise.
 */
function verifyTableSecurityToken(string|int $tableNo, ?string $token): bool {
    if ($token === null || trim($token) === '') {
        return false;
    }
    $expected = getTableSecurityToken($tableNo);
    return hash_equals(strtolower($expected), strtolower(trim($token)));
}
