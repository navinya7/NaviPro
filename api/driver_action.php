<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
if (!isDriverLoggedIn()) {
    if (isset($_GET['action'])) { header('Location: '.APP_URL.'/pages/driver_login.php'); exit; }
    jsonError('Unauthorized', 401);
}

$driverId = (int)$_SESSION['user_id'];
$db       = Database::getInstance();

// Accept params from GET (link click) or POST (AJAX)
$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = sanitize($_GET['action'] ?? $data['action'] ?? '');
$rideId = (int)($_GET['id']     ?? $data['id']     ?? 0);
$otp    = trim($_POST['otp']    ?? $data['otp']    ?? '');

if (!$rideId) {
    if (isset($_GET['action'])) { header('Location: '.APP_URL.'/pages/driver_dashboard.php'); exit; }
    jsonError('Ride ID required');
}

$ride = $db->fetch("SELECT * FROM rides WHERE id = ?", [$rideId]);
if (!$ride) {
    if (isset($_GET['action'])) { header('Location: '.APP_URL.'/pages/driver_dashboard.php'); exit; }
    jsonError('Ride not found');
}

$driver = $db->fetch("SELECT * FROM drivers WHERE id = ?", [$driverId]);

switch ($action) {

    case 'accept':
        // Check ride is still available
        if ($ride['status'] !== 'searching' || $ride['driver_id'] !== null) {
            Toast_redirect('This ride has already been taken.', 'warning');
        }
        // Check driver is not already on a ride
        $busy = $db->fetch(
            "SELECT id FROM rides WHERE driver_id = ? AND status NOT IN ('completed','cancelled')",
            [$driverId]
        );
        if ($busy) { Toast_redirect('Finish your current ride first.', 'warning'); }

        $affected = $db->execute(
            "UPDATE rides SET driver_id = ?, status = 'accepted', accepted_at = NOW()
             WHERE id = ? AND status = 'searching' AND driver_id IS NULL",
            [$driverId, $rideId]
        );
        if ($affected === 0) { Toast_redirect('Ride was just taken by another driver.', 'warning'); }

        $db->execute("UPDATE drivers SET is_available = 0 WHERE id = ?", [$driverId]);
        sendNotification('user', (int)$ride['user_id'], 'Driver Found! 🎉',
            "Your driver {$driver['full_name']} accepted your ride #{$ride['ride_code']}. They are on the way!");
        sendNotification('driver', $driverId, 'Ride Accepted',
            "You accepted ride #{$ride['ride_code']}. Head to the pickup location.");
        Toast_redirect('Ride accepted! Head to the pickup location.', 'success');
        break;

    case 'reject':
        // Just go back to dashboard to see next ride
        header('Location: '.APP_URL.'/pages/driver_dashboard.php');
        exit;

    case 'arrived':
        if ((int)$ride['driver_id'] !== $driverId) { Toast_redirect('Not your ride.', 'error'); }
        if ($ride['status'] !== 'accepted')          { Toast_redirect('Cannot mark arrived at this stage.', 'warning'); }
        $db->execute("UPDATE rides SET status = 'arrived', arrived_at = NOW() WHERE id = ?", [$rideId]);
        sendNotification('user', (int)$ride['user_id'], 'Driver Arrived! 📍',
            "Your driver has arrived at the pickup point. Please come quickly!");
        Toast_redirect('Marked as arrived. Ask the rider for their OTP.', 'success');
        break;

    case 'start':
        // Called via AJAX POST
        if ((int)$ride['driver_id'] !== $driverId) { jsonError('Not your ride'); }
        if ($ride['status'] !== 'arrived')          { jsonError('Cannot start — mark arrived first'); }
        if ($otp !== $ride['otp_for_start'])        { jsonError('Wrong OTP. Ask the rider again.'); }
        $db->execute("UPDATE rides SET status = 'started', started_at = NOW() WHERE id = ?", [$rideId]);
        sendNotification('user', (int)$ride['user_id'], 'Ride Started! 🚀',
            "Your trip has begun. Estimated fare: ₹{$ride['estimated_fare']}. Have a safe journey!");
        jsonSuccess([], 'Ride started!');
        break;  // jsonSuccess exits

    case 'complete':
        if ((int)$ride['driver_id'] !== $driverId) { Toast_redirect('Not your ride.', 'error'); }
        if ($ride['status'] !== 'started')          { Toast_redirect('Ride is not started yet.', 'warning'); }

        $finalFare = (float)$ride['estimated_fare'];
        $db->execute(
            "UPDATE rides SET status = 'completed', completed_at = NOW(), final_fare = ?,
                              payment_status = 'paid' WHERE id = ?",
            [$finalFare, $rideId]
        );
        $db->execute("UPDATE drivers SET is_available = 1, total_rides = total_rides + 1 WHERE id = ?", [$driverId]);
        $db->execute("UPDATE users SET total_rides = total_rides + 1 WHERE id = ?", [(int)$ride['user_id']]);

        // Payment
        if ($ride['payment_method'] === 'wallet') {
            debitWallet('user', (int)$ride['user_id'], $finalFare,
                "Ride payment #{$ride['ride_code']}", $rideId);
        }
        // Driver earns 80%
        $driverEarning = round($finalFare * 0.80, 2);
        creditWallet('driver', $driverId, $driverEarning,
            "Ride earnings #{$ride['ride_code']}", $rideId);

        sendNotification('user', (int)$ride['user_id'], 'Ride Completed! ✅',
            "Your ride #{$ride['ride_code']} is done. Fare: ₹{$finalFare}. Please rate your driver!");
        Toast_redirect('Ride completed! ₹'.$driverEarning.' added to your wallet.', 'success');
        break;

    default:
        header('Location: '.APP_URL.'/pages/driver_dashboard.php');
        exit;
}

// Helper: redirect to driver dashboard with a toast message via query param
function Toast_redirect(string $msg, string $type = 'info'): void {
    header('Location: '.APP_URL.'/pages/driver_dashboard.php?toast='.urlencode($msg).'&type='.$type);
    exit;
}
