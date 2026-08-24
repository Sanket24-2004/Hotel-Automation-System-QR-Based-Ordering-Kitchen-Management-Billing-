<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed. Use GET.'], 405);
}

$pdo = getDB();

try {
    $stmt = $pdo->query("SELECT * FROM hotel_settings WHERE id = 1 LIMIT 1");
    $settings = $stmt->fetch();

    if (!$settings) {
        $settings = [
            'geofence_enabled' => 0,
            'hotel_lat' => 18.5204303,
            'hotel_lng' => 73.8567437,
            'radius_meters' => 150,
            'daily_passcode' => '1234',
            'wifi_bypass_enabled' => 1
        ];
    }

    // Determine if customer is connected via Hotel local Wi-Fi / LAN
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $isLocalWifi = false;
    
    if (
        $clientIp === '127.0.0.1' || 
        $clientIp === '::1' || 
        str_starts_with($clientIp, '192.168.') || 
        str_starts_with($clientIp, '10.') || 
        preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $clientIp)
    ) {
        $isLocalWifi = true;
    }

    $isOwner = isset($_GET['is_owner']) && $_GET['is_owner'] === '1';

    $response = [
        'success'             => true,
        'geofence_enabled'    => (bool)$settings['geofence_enabled'],
        'hotel_lat'           => (float)$settings['hotel_lat'],
        'hotel_lng'           => (float)$settings['hotel_lng'],
        'radius_meters'       => (int)$settings['radius_meters'],
        'wifi_bypass_enabled' => (bool)$settings['wifi_bypass_enabled'],
        'is_on_hotel_wifi'    => $isLocalWifi,
    ];

    // Only expose actual passcode if owner requested from dashboard
    if ($isOwner) {
        $response['daily_passcode'] = (string)$settings['daily_passcode'];
    }

    jsonResponse($response);

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
}
