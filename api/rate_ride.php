<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
if (!isUserLoggedIn()) { jsonError('Unauthorized', 401); }
$data = json_decode(file_get_contents('php://input'), true) ?? [];
$rideId = (int)($data['ride_id'] ?? 0);
$rating = (int)($data['rating'] ?? 0);
$review = sanitize($data['review'] ?? '');
if ($rating < 1 || $rating > 5) { jsonError('Rating must be 1-5'); }
$db = Database::getInstance();
$ride = $db->fetch("SELECT * FROM rides WHERE id=? AND user_id=? AND status='completed'", [$rideId, $_SESSION['user_id']]);
if (!$ride) { jsonError('Ride not found or not completed'); }
$db->execute("UPDATE rides SET driver_rating=?, user_review=? WHERE id=?", [$rating, $review, $rideId]);
// Update driver average rating
if ($ride['driver_id']) {
    $avg = $db->fetch("SELECT AVG(driver_rating) as avg FROM rides WHERE driver_id=? AND driver_rating IS NOT NULL", [$ride['driver_id']]);
    $db->execute("UPDATE drivers SET rating=? WHERE id=?", [round($avg['avg'],2), $ride['driver_id']]);
}
jsonSuccess([], 'Rating submitted!');
