<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed. Use POST.'], 405);
}

$body = getJsonBody();
$lat = isset($body['lat']) ? (float)$body['lat'] : null;
$lng = isset($body['lng']) ? (float)$body['lng'] : null;
$accuracy = isset($body['accuracy']) ? (float)$body['accuracy'] : 25.0;
$tableNo = trim((string)($body['table'] ?? ''));
$tableToken = trim((string)($body['table_token'] ?? $body['token'] ?? ''));

function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $earthRadius = 6371000;
    $latDelta = deg2rad($lat2 - $lat1);
    $lonDelta = deg2rad($lon2 - $lon1);
    $a = sin($latDelta / 2) * sin($latDelta / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lonDelta / 2) * sin($lonDelta / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

$pdo = getDB();
try {
    $stmt = $pdo->query("SELECT * FROM hotel_settings WHERE id = 1 LIMIT 1");
    $settings = $stmt ? $stmt->fetch() : null;

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

    $geofenceEnabled = (int)($settings['geofence_enabled'] ?? 0) === 1;
    $wifiBypass = (int)($settings['wifi_bypass_enabled'] ?? 1) === 1;

    // 1. If geofence is disabled -> pass
    if (!$geofenceEnabled) {
        $token = hash('sha256', (string)$settings['daily_passcode'] . date('Y-m-d') . 'hotel_tulsi_secret');
        jsonResponse([
            'success' => true,
            'token' => $token,
            'method' => 'disabled',
            'message' => 'Location verification bypassed.'
        ]);
    }

    // 2. If Wi-Fi bypass enabled and client is on local network -> pass
    if ($wifiBypass && $isLocalWifi) {
        $token = hash('sha256', (string)$settings['daily_passcode'] . date('Y-m-d') . 'hotel_tulsi_secret');
        jsonResponse([
            'success' => true,
            'token' => $token,
            'method' => 'wifi',
            'message' => 'Verified via Hotel Wi-Fi.'
        ]);
    }

    // 3. Verify GPS
    if ($lat === null || $lng === null || $lat == 0 || $lng == 0) {
        jsonResponse([
            'success' => false,
            'error' => 'GPS coordinates required for verification.',
            'need_passcode' => true
        ], 400);
    }

    $hotelLat = (float)$settings['hotel_lat'];
    $hotelLng = (float)$settings['hotel_lng'];
    $radius = (float)$settings['radius_meters'];

    $rawDist = calculateDistanceMeters($lat, $lng, $hotelLat, $hotelLng);
    // Dilution of Precision indoor buffer: raw distance minus portion of accuracy circle
    $effectiveDist = max(0.0, $rawDist - ($accuracy * 0.75));

    if ($effectiveDist <= ($radius + 30.0)) {
        $token = hash('sha256', (string)$settings['daily_passcode'] . date('Y-m-d') . 'hotel_tulsi_secret');
        jsonResponse([
            'success' => true,
            'token' => $token,
            'distance' => round($rawDist, 1),
            'method' => 'gps',
            'message' => 'Location verified at Hotel Tulsi.'
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'distance' => round($rawDist, 1),
            'radius' => (int)$radius,
            'need_passcode' => true,
            'error' => "You are " . round($rawDist) . "m away from Hotel Tulsi (allowed radius: {$radius}m)."
        ], 403);
    }

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
}
