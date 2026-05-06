<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
if (!isUserLoggedIn()) { jsonError('Unauthorized', 401); }

$data       = json_decode(file_get_contents('php://input'), true) ?? [];
$db         = Database::getInstance();
$userId     = (int)$_SESSION['user_id'];

$catId      = (int)($data['category_id']    ?? 0);
$pickupAddr = trim($data['pickup_address']  ?? '');
$pickupLat  = (float)($data['pickup_lat']   ?? 0);
$pickupLng  = (float)($data['pickup_lng']   ?? 0);
$dropAddr   = trim($data['dropoff_address'] ?? '');
$dropLat    = (float)($data['dropoff_lat']  ?? 0);
$dropLng    = (float)($data['dropoff_lng']  ?? 0);
$distKm     = (float)($data['distance_km']  ?? 1);
$payMethod  = in_array($data['payment_method'] ?? '', ['cash','wallet','upi'])
              ? $data['payment_method'] : 'cash';
$promoId    = !empty($data['promo_id']) ? (int)$data['promo_id'] : null;

if (!$catId || !$pickupAddr || !$dropAddr || !$pickupLat || !$dropLat) {
    jsonError('Missing required fields');
}

$cat = $db->fetch("SELECT * FROM ride_categories WHERE id = ? AND is_active = 1", [$catId]);
if (!$cat) { jsonError('Invalid ride category'); }

// No duplicate active rides
$existing = $db->fetch(
    "SELECT id FROM rides WHERE user_id = ? AND status NOT IN ('completed','cancelled')",
    [$userId]
);
if ($existing) { jsonError('You already have an active ride. Please complete or cancel it first.'); }

// Fare calculation
$durationMins = max(5, (int)($distKm * 3));
$surge        = getSurgeMultiplier();
$raw          = ($cat['base_fare'] + $distKm * $cat['per_km_rate'] + $durationMins * $cat['per_min_rate']) * $surge;
$fare         = round(max($raw, $cat['min_fare']), 2);

// Apply promo code
if ($promoId) {
    $promo = $db->fetch("SELECT * FROM promo_codes WHERE id = ? AND is_active = 1", [$promoId]);
    if ($promo) {
        $disc = $promo['discount_type'] === 'percentage'
            ? ($fare * $promo['discount_value'] / 100)
            : (float)$promo['discount_value'];
        if ($promo['max_discount']) $disc = min($disc, (float)$promo['max_discount']);
        $fare = round(max($fare - $disc, 0), 2);
        $db->execute("UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?", [$promoId]);
    }
}

// Wallet balance check
if ($payMethod === 'wallet') {
    $user = $db->fetch("SELECT wallet_balance FROM users WHERE id = ?", [$userId]);
    if ((float)$user['wallet_balance'] < $fare) {
        jsonError('Insufficient wallet balance. Please add money or choose Cash/UPI.');
    }
}

// Create ride — always 'searching', driver must manually accept
$otp      = generateRideOTP();
$rideCode = generateRideCode();
$rideId   = (int)$db->insert(
    "INSERT INTO rides
       (ride_code, user_id, category_id,
        pickup_address,  pickup_lat,  pickup_lng,
        dropoff_address, dropoff_lat, dropoff_lng,
        distance_km, duration_mins, estimated_fare,
        surge_multiplier, payment_method, status, otp_for_start)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,'searching',?)",
    [$rideCode, $userId, $catId,
     $pickupAddr, $pickupLat, $pickupLng,
     $dropAddr,   $dropLat,   $dropLng,
     $distKm, $durationMins, $fare,
     $surge, $payMethod, $otp]
);

sendNotification('user', $userId, 'Ride Requested!',
    "Looking for a driver near you for ride #{$rideCode}");

// Return ride info — driver will accept separately from their dashboard
jsonSuccess([
    'ride_id'   => $rideId,
    'ride_code' => $rideCode,
    'status'    => 'searching',
    'otp'       => $otp,
    'fare'      => $fare,
], 'Ride posted! Waiting for a driver to accept.');
