<?php
// ============================================================
// FlashRide — Helper Functions
// ============================================================
require_once __DIR__ . '/../config/database.php';

// ---------- Session ----------
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params(['lifetime' => SESSION_LIFETIME, 'httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}

function isUserLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['user_id']) && ($_SESSION['user_type'] ?? '') === 'user';
}
function isDriverLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['user_id']) && ($_SESSION['user_type'] ?? '') === 'driver';
}
function isAdminLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['user_id']) && ($_SESSION['user_type'] ?? '') === 'admin';
}
function requireUserLogin(): void {
    if (!isUserLoggedIn()) {
        header('Location: ' . APP_URL . '/pages/user_login.php');
        exit;
    }
}
function requireDriverLogin(): void {
    if (!isDriverLoggedIn()) {
        header('Location: ' . APP_URL . '/pages/driver_login.php');
        exit;
    }
}
function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: ' . APP_URL . '/admin/login.php');
        exit;
    }
}

// ---------- Security ----------
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
function generateOTP(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}
function generateRideOTP(): string {
    return str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}
function generateRideCode(): string {
    return 'FR' . strtoupper(substr(uniqid(), -6)) . random_int(10, 99);
}

// ---------- Pricing ----------
function getSurgeMultiplier(): float {
    $h = (int)date('G');
    $morning = ($h >= SURGE_START_MORNING && $h < SURGE_END_MORNING);
    $evening = ($h >= SURGE_START_EVENING && $h < SURGE_END_EVENING);
    return ($morning || $evening) ? (float)SURGE_MULTIPLIER : 1.0;
}

function calculateFare(int $categoryId, float $distanceKm, int $durationMins): array {
    try {
        $db  = Database::getInstance();
        $cat = $db->fetch("SELECT * FROM ride_categories WHERE id = ?", [$categoryId]);
        if (!$cat) return ['error' => 'Category not found'];
        $surge    = getSurgeMultiplier();
        $raw      = ($cat['base_fare'] + $distanceKm * $cat['per_km_rate'] + $durationMins * $cat['per_min_rate']) * $surge;
        $fare     = max($raw, $cat['min_fare']);
        return [
            'base_fare'        => round($cat['base_fare'], 2),
            'distance_fare'    => round($distanceKm * $cat['per_km_rate'], 2),
            'time_fare'        => round($durationMins * $cat['per_min_rate'], 2),
            'surge_multiplier' => $surge,
            'estimated_fare'   => round($fare, 2),
            'is_surge'         => $surge > 1,
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $R    = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a    = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2)**2;
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

// ---------- Wallet ----------
function creditWallet(string $userType, int $userId, float $amount, string $desc, ?int $rideId = null): bool {
    try {
        $db    = Database::getInstance();
        $table = $userType === 'driver' ? 'drivers' : 'users';
        $row   = $db->fetch("SELECT wallet_balance FROM {$table} WHERE id = ?", [$userId]);
        if (!$row || !isset($row['wallet_balance'])) return false;
        $newBal = round((float)$row['wallet_balance'] + $amount, 2);
        $db->execute("UPDATE {$table} SET wallet_balance = ? WHERE id = ?", [$newBal, $userId]);
        $db->insert(
            "INSERT INTO wallet_transactions (user_type, user_id, ride_id, type, amount, balance_after, description) VALUES (?,?,?,?,?,?,?)",
            [$userType, $userId, $rideId, 'credit', $amount, $newBal, $desc]
        );
        return true;
    } catch (Exception $e) { return false; }
}

function debitWallet(string $userType, int $userId, float $amount, string $desc, ?int $rideId = null): bool {
    try {
        $db    = Database::getInstance();
        $table = $userType === 'driver' ? 'drivers' : 'users';
        $row   = $db->fetch("SELECT wallet_balance FROM {$table} WHERE id = ?", [$userId]);
        if (!$row || !isset($row['wallet_balance']) || (float)$row['wallet_balance'] < $amount) return false;
        $newBal = round((float)$row['wallet_balance'] - $amount, 2);
        $db->execute("UPDATE {$table} SET wallet_balance = ? WHERE id = ?", [$newBal, $userId]);
        $db->insert(
            "INSERT INTO wallet_transactions (user_type, user_id, ride_id, type, amount, balance_after, description) VALUES (?,?,?,?,?,?,?)",
            [$userType, $userId, $rideId, 'debit', $amount, $newBal, $desc]
        );
        return true;
    } catch (Exception $e) { return false; }
}

// ---------- Notifications ----------
function sendNotification(string $recipientType, int $recipientId, string $title, string $message, string $type = 'general'): void {
    try {
        Database::getInstance()->insert(
            "INSERT INTO notifications (recipient_type, recipient_id, title, message, type) VALUES (?,?,?,?,?)",
            [$recipientType, $recipientId, $title, $message, $type]
        );
    } catch (Exception $e) { /* silent */ }
}

// ---------- Promo ----------
function applyPromoCode(string $code, float $fareAmount): array {
    try {
        $db    = Database::getInstance();
        $promo = $db->fetch(
            "SELECT * FROM promo_codes WHERE code = ? AND is_active = 1 AND valid_from <= CURDATE() AND valid_until >= CURDATE()",
            [strtoupper($code)]
        );
        if (!$promo)                                  return ['success' => false, 'message' => 'Invalid or expired promo code'];
        if ($promo['usage_limit'] && $promo['used_count'] >= $promo['usage_limit'])
                                                      return ['success' => false, 'message' => 'Promo code usage limit reached'];
        if ($fareAmount < $promo['min_ride_amount'])  return ['success' => false, 'message' => 'Minimum ride amount ₹' . $promo['min_ride_amount'] . ' required'];

        $discount = $promo['discount_type'] === 'percentage'
            ? ($fareAmount * $promo['discount_value'] / 100)
            : (float)$promo['discount_value'];
        if ($promo['max_discount']) $discount = min($discount, (float)$promo['max_discount']);
        $discount = min($discount, $fareAmount);
        return ['success' => true, 'discount' => round($discount, 2), 'final_fare' => round($fareAmount - $discount, 2), 'promo_id' => $promo['id']];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Could not apply promo code'];
    }
}

// ---------- JSON responses ----------
function jsonSuccess(array $data = [], string $message = 'Success'): void {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => $message, 'data' => $data]);
    exit;
}
function jsonError(string $message = 'Error', int $code = 400): void {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

// ---------- Formatting ----------
function formatCurrency(float $amount): string {
    return '₹' . number_format($amount, 2);
}
function timeAgo(string $datetime): string {
    $diff = (new DateTime())->diff(new DateTime($datetime));
    if ($diff->i < 1)  return 'Just now';
    if ($diff->h < 1)  return $diff->i . 'm ago';
    if ($diff->days < 1) return $diff->h . 'h ago';
    return $diff->days . 'd ago';
}
function getStatusBadge(string $status): string {
    return [
        'searching'  => 'warning',
        'accepted'   => 'info',
        'arrived'    => 'primary',
        'started'    => 'purple',
        'completed'  => 'success',
        'cancelled'  => 'danger',
        'pending'    => 'warning',
        'paid'       => 'success',
        'active'     => 'success',
        'suspended'  => 'danger',
    ][$status] ?? 'secondary';
}
