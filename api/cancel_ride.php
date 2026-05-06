<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();

// Accept from GET (direct link) or POST JSON (AJAX)
$isAjax  = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
         || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

$data    = [];
$rideId  = 0;
$reason  = 'Cancelled by user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw    = file_get_contents('php://input');
    $data   = json_decode($raw, true) ?? $_POST;
    $rideId = (int)($data['ride_id'] ?? $data['id'] ?? 0);
    $reason = trim($data['reason'] ?? 'Cancelled by user');
} else {
    $rideId = (int)($_GET['id'] ?? 0);
    $reason = trim($_GET['reason'] ?? 'Cancelled by user');
}

if (!$rideId) {
    if (!$isAjax) { header('Location: ' . APP_URL . '/pages/dashboard.php'); exit; }
    jsonError('Ride ID required');
}

try {
    $db = Database::getInstance();

    // Determine who is cancelling
    if (isUserLoggedIn()) {
        $ride        = $db->fetch("SELECT * FROM rides WHERE id = ? AND user_id = ?", [$rideId, (int)$_SESSION['user_id']]);
        $cancelledBy = 'user';
    } elseif (isDriverLoggedIn()) {
        $ride        = $db->fetch("SELECT * FROM rides WHERE id = ? AND driver_id = ?", [$rideId, (int)$_SESSION['user_id']]);
        $cancelledBy = 'driver';
    } elseif (isAdminLoggedIn()) {
        $ride        = $db->fetch("SELECT * FROM rides WHERE id = ?", [$rideId]);
        $cancelledBy = 'admin';
    } else {
        if (!$isAjax) { header('Location: ' . APP_URL . '/pages/user_login.php'); exit; }
        jsonError('Not logged in', 401);
    }

    if (!$ride) {
        if (!$isAjax) { header('Location: ' . APP_URL . '/pages/dashboard.php'); exit; }
        jsonError('Ride not found or you do not have permission to cancel it');
    }

    // Can only cancel if not already completed/cancelled
    if (in_array($ride['status'], ['completed', 'cancelled'])) {
        if (!$isAjax) { header('Location: ' . APP_URL . '/pages/dashboard.php?msg=already_cancelled'); exit; }
        jsonError('This ride cannot be cancelled — it is already ' . $ride['status']);
    }

    // Cannot cancel once ride has started
    if ($ride['status'] === 'started' && $cancelledBy !== 'admin') {
        if (!$isAjax) { header('Location: ' . APP_URL . '/pages/dashboard.php'); exit; }
        jsonError('Cannot cancel a ride that has already started');
    }

    // Cancel the ride
    $db->execute(
        "UPDATE rides SET status='cancelled', cancel_reason=?, cancelled_by=? WHERE id=?",
        [$reason, $cancelledBy, $rideId]
    );

    // Free up driver
    if ($ride['driver_id']) {
        $db->execute("UPDATE drivers SET is_available=1 WHERE id=?", [$ride['driver_id']]);
    }

    // Refund wallet payment if applicable
    if ($ride['payment_method'] === 'wallet' && $ride['payment_status'] === 'paid') {
        creditWallet('user', (int)$ride['user_id'], (float)$ride['estimated_fare'],
            'Refund for cancelled ride #' . $ride['ride_code'], $rideId);
        $db->execute("UPDATE rides SET payment_status='refunded' WHERE id=?", [$rideId]);
    }

    // Notify both parties
    if ($cancelledBy === 'user') {
        sendNotification('user', (int)$ride['user_id'], 'Ride Cancelled',
            'Your ride #' . $ride['ride_code'] . ' has been cancelled. Reason: ' . $reason);
        if ($ride['driver_id']) {
            sendNotification('driver', (int)$ride['driver_id'], 'Ride Cancelled by Rider',
                'Ride #' . $ride['ride_code'] . ' was cancelled. Reason: ' . $reason);
        }
    } elseif ($cancelledBy === 'driver') {
        sendNotification('user', (int)$ride['user_id'], 'Driver Cancelled Your Ride',
            'Your driver cancelled ride #' . $ride['ride_code'] . '. Please book again. Reason: ' . $reason);
        if ($ride['driver_id']) {
            sendNotification('driver', (int)$ride['driver_id'], 'Ride Cancelled',
                'You cancelled ride #' . $ride['ride_code']);
        }
    }

    // Respond
    if (!$isAjax) {
        header('Location: ' . APP_URL . '/pages/dashboard.php');
        exit;
    }
    jsonSuccess(['ride_id' => $rideId, 'status' => 'cancelled'], 'Ride cancelled successfully');

} catch (Exception $e) {
    if (!$isAjax) { header('Location: ' . APP_URL . '/pages/dashboard.php'); exit; }
    jsonError('Cancellation failed: ' . $e->getMessage());
}
