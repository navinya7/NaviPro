<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
if (!isUserLoggedIn()) { jsonError('Unauthorized', 401); }
$data = json_decode(file_get_contents('php://input'), true) ?? [];
$rideId = (int)($data['ride_id'] ?? 0);
$db = Database::getInstance();
$ride = $db->fetch("SELECT * FROM rides WHERE id=? AND user_id=?", [$rideId, $_SESSION['user_id']]);
if (!$ride) { jsonError('Ride not found'); }
$db->insert("INSERT INTO sos_alerts (ride_id,user_id,message) VALUES (?,?,?)", [$rideId, $_SESSION['user_id'], 'SOS triggered by user']);
sendNotification('user', $_SESSION['user_id'], '🚨 SOS Sent', 'Emergency services have been notified. Help is on the way!');
jsonSuccess([], 'SOS alert sent!');
