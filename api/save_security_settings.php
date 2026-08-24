<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed. Use POST.'], 405);
}

$body = getJsonBody();
$pdo  = getDB();

$geofenceEnabled  = isset($body['geofence_enabled']) ? (int)(bool)$body['geofence_enabled'] : 0;
$hotelLat         = isset($body['hotel_lat']) ? (float)$body['hotel_lat'] : 18.5204303;
$hotelLng         = isset($body['hotel_lng']) ? (float)$body['hotel_lng'] : 73.8567437;
$radiusMeters     = isset($body['radius_meters']) ? max(20, (int)$body['radius_meters']) : 150;
$dailyPasscode    = isset($body['daily_passcode']) ? trim((string)$body['daily_passcode']) : '1234';
$wifiBypass       = isset($body['wifi_bypass_enabled']) ? (int)(bool)$body['wifi_bypass_enabled'] : 1;

try {
    $stmt = $pdo->prepare("
        INSERT INTO hotel_settings (id, geofence_enabled, hotel_lat, hotel_lng, radius_meters, daily_passcode, wifi_bypass_enabled)
        VALUES (1, :geo, :lat, :lng, :radius, :passcode, :wifi)
        ON DUPLICATE KEY UPDATE
            geofence_enabled = VALUES(geofence_enabled),
            hotel_lat = VALUES(hotel_lat),
            hotel_lng = VALUES(hotel_lng),
            radius_meters = VALUES(radius_meters),
            daily_passcode = VALUES(daily_passcode),
            wifi_bypass_enabled = VALUES(wifi_bypass_enabled)
    ");
    $stmt->execute([
        ':geo'      => $geofenceEnabled,
        ':lat'      => $hotelLat,
        ':lng'      => $hotelLng,
        ':radius'   => $radiusMeters,
        ':passcode' => $dailyPasscode,
        ':wifi'     => $wifiBypass
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Security settings updated successfully.'
    ]);

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Failed to save settings: ' . $e->getMessage()], 500);
}
