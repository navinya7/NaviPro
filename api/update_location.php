<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
if (!isDriverLoggedIn()) { jsonError('Unauthorized', 401); }
$data = json_decode(file_get_contents('php://input'), true) ?? [];
$lat = (float)($data['lat'] ?? 0);
$lng = (float)($data['lng'] ?? 0);
if (!$lat || !$lng) { jsonError('Invalid coordinates'); }
$db = Database::getInstance();
$db->execute("UPDATE drivers SET current_lat=?, current_lng=? WHERE id=?", [$lat, $lng, $_SESSION['user_id']]);
// Log tracking if active ride
$activeRide = $db->fetch("SELECT id FROM rides WHERE driver_id=? AND status='started'", [$_SESSION['user_id']]);
if ($activeRide) {
    $db->insert("INSERT INTO ride_tracking (ride_id,lat,lng) VALUES (?,?,?)", [$activeRide['id'], $lat, $lng]);
}
jsonSuccess([], 'Location updated');
