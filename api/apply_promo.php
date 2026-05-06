<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
if (!isUserLoggedIn()) { jsonError('Unauthorized', 401); }
$data = json_decode(file_get_contents('php://input'), true) ?? [];
$code = strtoupper(sanitize($data['code'] ?? ''));
$fare = (float)($data['fare'] ?? 0);
if (!$code || !$fare) { jsonError('Code and fare required'); }
$result = applyPromoCode($code, $fare);
if ($result['success']) jsonSuccess(['discount' => $result['discount'], 'final_fare' => $result['final_fare'], 'promo_id' => $result['promo_id']], 'Promo applied!');
else jsonError($result['message']);
