<?php
// ============================================================
// FlashRide — HTML Header Include
// ============================================================
if (!defined('APP_URL')) {
    require_once __DIR__ . '/../config/database.php';
}
if (!function_exists('startSession')) {
    require_once __DIR__ . '/functions.php';
}
startSession();

$pageTitle = isset($pageTitle) ? $pageTitle . ' | FlashRide' : 'FlashRide — Ride Smart, Arrive Fast';
$bodyClass  = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="FlashRide — India's fastest ride booking app. Book bikes, autos and cabs instantly.">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/images/logo.svg">
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Leaflet map CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <!-- App CSS -->
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">
