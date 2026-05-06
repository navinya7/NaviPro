<?php
// ============================================================
// FlashRide — Setup Checker
// Visit: http://localhost/flashride/setup_check.php
// DELETE this file after setup is complete!
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

$checks = [];

// 1. PHP version
$phpOk = version_compare(PHP_VERSION, '7.4.0', '>=');
$checks[] = ['PHP Version (' . PHP_VERSION . ')', $phpOk, $phpOk ? 'OK' : 'Need PHP 7.4+'];

// 2. PDO
$pdoOk = extension_loaded('pdo') && extension_loaded('pdo_mysql');
$checks[] = ['PDO MySQL Extension', $pdoOk, $pdoOk ? 'OK' : 'Enable pdo_mysql in php.ini'];

// 3. Session
$sessionOk = function_exists('session_start');
$checks[] = ['Sessions', $sessionOk, $sessionOk ? 'OK' : 'Sessions not available'];

// 4. uploads dir writable
$uploadDir = __DIR__ . '/assets/images/uploads/';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
$uploadOk = is_writable($uploadDir);
$checks[] = ['Uploads Directory Writable', $uploadOk, $uploadOk ? 'OK' : 'Run: chmod 755 assets/images/uploads/'];

// 5. DB connection
require_once __DIR__ . '/config/database.php';
$dbOk = false; $dbMsg = '';
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $dbOk  = true;
    $dbMsg = 'Connected to MySQL as ' . DB_USER . '@' . DB_HOST;
} catch (PDOException $e) {
    $dbMsg = 'FAILED: ' . $e->getMessage();
}
$checks[] = ['MySQL Connection', $dbOk, $dbMsg];

// 6. DB exists
$dbExistsOk = false; $dbExistsMsg = '';
if ($dbOk) {
    try {
        $pdo->exec("USE `" . DB_NAME . "`");
        $dbExistsOk  = true;
        $dbExistsMsg = 'Database "' . DB_NAME . '" found';
    } catch (PDOException $e) {
        $dbExistsMsg = 'Database "' . DB_NAME . '" not found — import database.sql first!';
    }
}
$checks[] = ['Database "' . DB_NAME . '"', $dbExistsOk, $dbExistsMsg];

// 7. Tables exist
$tablesOk = false; $tablesMsg = '';
if ($dbExistsOk) {
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $required = ['users','drivers','rides','ride_categories','wallet_transactions','notifications','admins'];
        $missing  = array_diff($required, $tables);
        $tablesOk  = empty($missing);
        $tablesMsg = $tablesOk ? 'All ' . count($tables) . ' tables found' : 'Missing: ' . implode(', ', $missing) . ' — re-import database.sql';
    } catch (PDOException $e) { $tablesMsg = $e->getMessage(); }
}
$checks[] = ['Database Tables', $tablesOk, $tablesMsg];

$allOk = !in_array(false, array_column($checks, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>FlashRide Setup Check</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{--primary:#FF6B00;--success:#00E676;--danger:#FF3B6B;--warning:#FFD600;--dark:#0A0A0F;--card:#1A1A26;--text:#F0F0FF;--muted:#9898B8;--border:rgba(255,255,255,.07)}
  *{margin:0;padding:0;box-sizing:border-box}
  body{background:var(--dark);color:var(--text);font-family:'DM Sans',sans-serif;padding:2rem;min-height:100vh;display:flex;align-items:center;justify-content:center;}
  .wrap{max-width:700px;width:100%}
  .logo{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;margin-bottom:.3rem}.logo span{color:var(--primary)}
  h1{font-family:'Syne',sans-serif;font-size:1.3rem;color:var(--muted);font-weight:600;margin-bottom:2rem}
  .card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:1.5rem;margin-bottom:1rem}
  .check{display:flex;align-items:flex-start;gap:1rem;padding:.75rem 0;border-bottom:1px solid var(--border)}
  .check:last-child{border-bottom:none}
  .dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0;margin-top:1px}
  .ok{background:rgba(0,230,118,.15);color:var(--success)}.fail{background:rgba(255,59,107,.15);color:var(--danger)}
  .check-name{font-weight:600;font-size:.95rem}
  .check-msg{font-size:.82rem;color:var(--muted);margin-top:.2rem}
  .check-msg.err{color:var(--danger)}
  .banner{border-radius:12px;padding:1.2rem 1.5rem;margin-bottom:1.5rem;font-weight:600}
  .banner.success{background:rgba(0,230,118,.1);border:1px solid var(--success);color:var(--success)}
  .banner.fail{background:rgba(255,59,107,.1);border:1px solid var(--danger);color:var(--danger)}
  .steps{background:rgba(255,107,0,.08);border:1px solid rgba(255,107,0,.2);border-radius:12px;padding:1.2rem 1.5rem;margin-top:1rem}
  .steps h3{font-family:'Syne',sans-serif;font-size:1rem;margin-bottom:.8rem;color:var(--primary)}
  .steps ol{padding-left:1.3rem;color:var(--muted);font-size:.88rem;line-height:2}
  .steps code{background:rgba(255,107,0,.15);color:var(--primary);padding:.1rem .4rem;border-radius:4px;font-family:monospace}
  a.btn{display:inline-block;margin-top:1.5rem;background:var(--primary);color:#fff;padding:.75rem 2rem;border-radius:50px;font-weight:700;text-decoration:none;font-family:'DM Sans',sans-serif}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo"><span>Flash</span>Ride</div>
  <h1>⚙️ Setup Checker</h1>

  <div class="banner <?= $allOk ? 'success' : 'fail' ?>">
    <?= $allOk ? '✅ Everything looks good! FlashRide is ready to run.' : '❌ Setup incomplete — fix the issues below.' ?>
  </div>

  <div class="card">
    <?php foreach ($checks as $c): ?>
    <div class="check">
      <div class="dot <?= $c[1] ? 'ok' : 'fail' ?>"><?= $c[1] ? '✓' : '✗' ?></div>
      <div>
        <div class="check-name"><?= htmlspecialchars($c[0]) ?></div>
        <div class="check-msg <?= $c[1] ? '' : 'err' ?>"><?= htmlspecialchars($c[2]) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if (!$allOk): ?>
  <div class="steps">
    <h3>📋 Quick Fix Steps</h3>
    <ol>
      <li>Open <strong>phpMyAdmin</strong> → click <strong>Import</strong></li>
      <li>Select <code>database.sql</code> from the project folder</li>
      <li>Click <strong>Go</strong> — this creates the DB and all tables</li>
      <li>Open <code>config/database.php</code> and set your <code>DB_USER</code> / <code>DB_PASS</code></li>
      <li>If DB_PASS error: in XAMPP, root usually has no password (leave blank)</li>
      <li>Reload this page to verify</li>
    </ol>
  </div>
  <?php endif; ?>

  <?php if ($allOk): ?>
  <a href="<?= APP_URL ?>/" class="btn">🚀 Open FlashRide</a>
  <p style="margin-top:1rem;font-size:.8rem;color:var(--muted);">⚠️ Delete <code>setup_check.php</code> before going live!</p>
  <?php endif; ?>
</div>
</body>
</html>
