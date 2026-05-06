<?php
// ============================================================
// FlashRide — Database Configuration
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // ← Change if needed
define('DB_PASS', '');            // ← Change if needed (blank for default XAMPP)
define('DB_NAME', 'flashride_db');
define('DB_PORT', 3306);

define('APP_NAME',    'FlashRide');
define('APP_URL',     'http://localhost/flashride');   // ← No trailing slash
define('APP_VERSION', '1.0.0');
define('APP_SECRET',  'flashride_secret_key_2025');

define('SESSION_LIFETIME',    86400);
define('UPLOAD_DIR',          __DIR__ . '/../assets/images/uploads/');
define('MAX_UPLOAD_SIZE',     5242880);
define('OTP_EXPIRY_MINUTES',  10);
define('SURGE_START_MORNING', 8);
define('SURGE_END_MORNING',   10);
define('SURGE_START_EVENING', 17);
define('SURGE_END_EVENING',   20);
define('SURGE_MULTIPLIER',    1.5);

// ============================================================
// Database class — PDO singleton
// ============================================================
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST
             . ';port='      . DB_PORT
             . ';dbname='    . DB_NAME
             . ';charset=utf8mb4';
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() { return $this->pdo; }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch($sql, $params = []) {
        $row = $this->query($sql, $params)->fetch();
        return $row ?: null;
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    public function execute($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }
}

// ============================================================
// Global DB error handler — shows a friendly setup page
// instead of a raw fatal error
// ============================================================
set_exception_handler(function($e) {
    // Only intercept DB / table-not-found errors
    $msg = $e->getMessage();
    $isDB = ($e instanceof PDOException)
         || strpos($msg, 'SQLSTATE') !== false
         || strpos($msg, 'Base table') !== false
         || strpos($msg, 'Table') !== false
         || strpos($msg, 'Connection refused') !== false
         || strpos($msg, 'Access denied') !== false
         || strpos($msg, 'Unknown database') !== false;

    if ($isDB) {
        // Determine APP_URL safely
        $base = defined('APP_URL') ? APP_URL : 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/flashride';
        $installUrl    = $base . '/install.php';
        $checkUrl      = $base . '/setup_check.php';
        $isTableMissing = strpos($msg, "doesn't exist") !== false || strpos($msg, 'Base table') !== false;
        $isNoDB         = strpos($msg, 'Unknown database') !== false;
        $isNoConn       = strpos($msg, 'Connection refused') !== false || strpos($msg, 'Access denied') !== false;

        $title  = 'Database Setup Required';
        $detail = htmlspecialchars($msg);

        if ($isTableMissing) {
            $fix = 'Tables are missing. You need to import <code>database.sql</code>.';
        } elseif ($isNoDB) {
            $fix = 'The database <strong>flashride_db</strong> does not exist. Create it first, then import <code>database.sql</code>.';
        } elseif ($isNoConn) {
            $fix = 'Cannot connect to MySQL. Make sure XAMPP MySQL is running.';
        } else {
            $fix = 'A database error occurred.';
        }
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>FlashRide — Setup Required</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0A0A0F;color:#F0F0FF;font-family:'Segoe UI',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem;}
.box{background:#1A1A26;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:2.5rem;max-width:620px;width:100%;}
.logo{font-size:1.8rem;font-weight:800;margin-bottom:1.5rem;}.logo span{color:#FF6B00;}
.badge{display:inline-block;background:rgba(255,59,107,.15);color:#FF3B6B;border:1px solid #FF3B6B;border-radius:6px;padding:.3rem .8rem;font-size:.82rem;font-weight:700;margin-bottom:1rem;}
h2{font-size:1.3rem;margin-bottom:.8rem;}
.fix{background:rgba(255,107,0,.08);border:1px solid rgba(255,107,0,.25);border-radius:10px;padding:1.2rem 1.4rem;margin:1.2rem 0;font-size:.9rem;line-height:1.8;}
.fix code{background:rgba(255,107,0,.2);color:#FF6B00;padding:.1rem .4rem;border-radius:4px;font-family:monospace;}
.steps{counter-reset:s;padding:0;margin:.8rem 0 0 0;list-style:none;}
.steps li{counter-increment:s;padding:.4rem 0 .4rem 2.2rem;position:relative;font-size:.88rem;color:#B0B0D0;}
.steps li::before{content:counter(s);position:absolute;left:0;top:.35rem;width:1.4rem;height:1.4rem;background:#FF6B00;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;color:#fff;}
.err{background:rgba(255,255,255,.04);border-radius:8px;padding:.8rem 1rem;margin-top:1rem;font-family:monospace;font-size:.75rem;color:#FF6B00;word-break:break-all;border:1px solid rgba(255,107,0,.2);}
.btns{display:flex;gap:.8rem;margin-top:1.5rem;flex-wrap:wrap;}
.btn{padding:.7rem 1.5rem;border-radius:50px;font-weight:700;font-size:.9rem;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;}
.btn-primary{background:#FF6B00;color:#fff;}
.btn-ghost{background:rgba(255,255,255,.07);color:#F0F0FF;border:1px solid rgba(255,255,255,.1);}
</style>
</head>
<body>
<div class="box">
  <div class="logo"><span>Flash</span>Ride</div>
  <div class="badge">⚠ Database Error</div>
  <h2>Setup Required</h2>
  <p style="color:#9898B8;font-size:.9rem;"><?= $fix ?></p>

  <div class="fix">
    <strong style="color:#F0F0FF;">How to fix in 3 steps:</strong>
    <ol class="steps">
      <li>Make sure <strong>XAMPP → MySQL</strong> is running (green in XAMPP control panel)</li>
      <li>Open <a href="<?= $installUrl ?>" style="color:#FF6B00;"><?= $installUrl ?></a> — click <strong>Install Database Now</strong></li>
      <li>Refresh this page — it will work!</li>
    </ol>
  </div>

  <p style="font-size:.78rem;color:#5A5A78;margin-top:.5rem;"><strong>OR manually:</strong> phpMyAdmin → New → create <code>flashride_db</code> → Import → select <code>database.sql</code></p>

  <details style="margin-top:1rem;">
    <summary style="cursor:pointer;font-size:.8rem;color:#5A5A78;">Show technical error</summary>
    <div class="err"><?= $detail ?></div>
  </details>

  <div class="btns">
    <a href="<?= $installUrl ?>" class="btn btn-primary">🚀 One-Click Install</a>
    <a href="<?= $checkUrl ?>"   class="btn btn-ghost">🔍 Check Setup</a>
    <a href="javascript:location.reload()" class="btn btn-ghost">↻ Retry</a>
  </div>
</div>
</body>
</html>
<?php
        exit;
    }
    // Non-DB exceptions: re-throw so normal PHP error handling applies
    throw $e;
});
