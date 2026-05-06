<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
$rideId = (int)($_GET['id'] ?? 0);
$db = Database::getInstance();
$ride = $db->fetch("SELECT r.*, d.full_name as driver_name, d.vehicle_number, d.vehicle_model, d.rating as driver_rating, d.current_lat as driver_lat, d.current_lng as driver_lng FROM rides r LEFT JOIN drivers d ON r.driver_id=d.id WHERE r.id=?", [$rideId]);
if (!$ride) { jsonError('Ride not found', 404); }
jsonSuccess(array_merge($ride, ['badge_class' => getStatusBadge($ride['status'])]));
