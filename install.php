<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$user = 'root';
$pass = '';
$port = 3306;
$dbName = 'flashride_db';
$errors = [];
$done   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? 'localhost');
    $user = trim($_POST['user'] ?? 'root');
    $pass = $_POST['pass'] ?? '';
    $port = (int)($_POST['port'] ?? 3306);

    try {
        // 1. Connect WITHOUT selecting a DB
        $pdo = new PDO(
            "mysql:host={$host};port={$port};charset=utf8mb4",
            $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => true]
        );
        $done[] = "✅ Connected to MySQL as <strong>{$user}@{$host}</strong>";

        // 2. Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");
        $done[] = "✅ Database <strong>{$dbName}</strong> ready";

        // 3. Drop all tables (clean install)
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        foreach (['sos_alerts','ride_tracking','rides','wallet_transactions','notifications','promo_codes','ride_categories','drivers','users','admins'] as $t) {
            $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        $done[] = "✅ Old tables cleared";

        // 4. Create all tables inline (no external file needed)
        $tables = [

"CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(15) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `profile_pic` VARCHAR(255) DEFAULT 'default_user.png',
  `wallet_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_rides` INT NOT NULL DEFAULT 0,
  `rating` DECIMAL(3,2) NOT NULL DEFAULT 5.00,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `otp_code` VARCHAR(6) DEFAULT NULL,
  `otp_expiry` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` DATETIME DEFAULT NULL,
  `status` ENUM('active','suspended','deleted') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE `drivers` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(15) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `profile_pic` VARCHAR(255) DEFAULT 'default_driver.png',
  `vehicle_type` ENUM('bike','auto','cab') NOT NULL,
  `vehicle_number` VARCHAR(20) NOT NULL,
  `vehicle_model` VARCHAR(100) DEFAULT NULL,
  `license_number` VARCHAR(50) NOT NULL,
  `wallet_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_rides` INT NOT NULL DEFAULT 0,
  `rating` DECIMAL(3,2) NOT NULL DEFAULT 5.00,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `current_lat` DECIMAL(10,7) DEFAULT NULL,
  `current_lng` DECIMAL(10,7) DEFAULT NULL,
  `otp_code` VARCHAR(6) DEFAULT NULL,
  `otp_expiry` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` DATETIME DEFAULT NULL,
  `status` ENUM('active','suspended','deleted') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_drivers_email` (`email`),
  UNIQUE KEY `uq_drivers_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE `ride_categories` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `type` ENUM('bike','auto','cab') NOT NULL,
  `icon` VARCHAR(100) DEFAULT NULL,
  `base_fare` DECIMAL(10,2) NOT NULL,
  `per_km_rate` DECIMAL(10,2) NOT NULL,
  `per_min_rate` DECIMAL(10,2) NOT NULL,
  `min_fare` DECIMAL(10,2) NOT NULL,
  `surge_multiplier` DECIMAL(4,2) NOT NULL DEFAULT 1.00,
  `max_passengers` INT NOT NULL DEFAULT 1,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE `rides` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ride_code` VARCHAR(20) NOT NULL,
  `user_id` INT NOT NULL,
  `driver_id` INT DEFAULT NULL,
  `category_id` INT NOT NULL,
  `pickup_address` TEXT NOT NULL,
  `pickup_lat` DECIMAL(10,7) NOT NULL,
  `pickup_lng` DECIMAL(10,7) NOT NULL,
  `dropoff_address` TEXT NOT NULL,
  `dropoff_lat` DECIMAL(10,7) NOT NULL,
  `dropoff_lng` DECIMAL(10,7) NOT NULL,
  `distance_km` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `duration_mins` INT NOT NULL DEFAULT 0,
  `estimated_fare` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `final_fare` DECIMAL(10,2) DEFAULT NULL,
  `surge_multiplier` DECIMAL(4,2) NOT NULL DEFAULT 1.00,
  `payment_method` ENUM('cash','wallet','upi') NOT NULL DEFAULT 'cash',
  `payment_status` ENUM('pending','paid','refunded') NOT NULL DEFAULT 'pending',
  `status` ENUM('searching','accepted','arrived','started','completed','cancelled') NOT NULL DEFAULT 'searching',
  `cancel_reason` TEXT DEFAULT NULL,
  `cancelled_by` ENUM('user','driver','admin') DEFAULT NULL,
  `otp_for_start` VARCHAR(4) DEFAULT NULL,
  `driver_rating` INT DEFAULT NULL,
  `user_rating` INT DEFAULT NULL,
  `driver_review` TEXT DEFAULT NULL,
  `user_review` TEXT DEFAULT NULL,
  `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `accepted_at` DATETIME DEFAULT NULL,
  `arrived_at` DATETIME DEFAULT NULL,
  `started_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rides_code` (`ride_code`),
  KEY `idx_rides_user` (`user_id`),
  KEY `idx_rides_driver` (`driver_id`),
  KEY `idx_rides_status` (`status`),
  CONSTRAINT `fk_rides_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  CONSTRAINT `fk_rides_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`),
  CONSTRAINT `fk_rides_cat` FOREIGN KEY (`category_id`) REFERENCES `ride_categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE `ride_tracking` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ride_id` INT NOT NULL,
  `lat` DECIMAL(10,7) NOT NULL,
  `lng` DECIMAL(10,7) NOT NULL,
  `speed` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `recorded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tracking_ride` (`ride_id`),
  CONSTRAINT `fk_tracking_ride` FOREIGN KEY (`ride_id`) REFERENCES `rides`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE `wallet_transactions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_type` ENUM('user','driver') NOT NULL,
  `user_id` INT NOT NULL,
  `ride_id` INT DEFAULT NULL,
  `type` ENUM('credit','debit') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `balance_after` DECIMAL(10,2) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `reference_id` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wallet_user` (`user_type`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE `promo_codes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(30) NOT NULL,
  `discount_type` ENUM('percentage','fixed') NOT NULL,
  `discount_value` DECIMAL(10,2) NOT NULL,
  `min_ride_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_discount` DECIMAL(10,2) DEFAULT NULL,
  `usage_limit` INT DEFAULT NULL,
  `used_count` INT NOT NULL DEFAULT 0,
  `valid_from` DATE NOT NULL,
  `valid_until` DATE NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_promo_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE `sos_alerts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ride_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `lat` DECIMAL(10,7) DEFAULT NULL,
  `lng` DECIMAL(10,7) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `status` ENUM('active','resolved') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_sos_ride` FOREIGN KEY (`ride_id`) REFERENCES `rides`(`id`),
  CONSTRAINT `fk_sos_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE `notifications` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `recipient_type` ENUM('user','driver') NOT NULL,
  `recipient_id` INT NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'general',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif` (`recipient_type`,`recipient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE `admins` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('superadmin','admin','support') NOT NULL DEFAULT 'admin',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_username` (`username`),
  UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        ];

        foreach ($tables as $sql) {
            $pdo->exec($sql);
        }
        $done[] = "✅ All <strong>10 tables</strong> created successfully";

        // 5. Seed data
        $pdo->exec("INSERT INTO `ride_categories` (name,type,icon,base_fare,per_km_rate,per_min_rate,min_fare,max_passengers,description) VALUES
('Flash Bike','bike','bike',15.00,8.00,1.00,25.00,1,'Fast & affordable bike rides'),
('Flash Auto','auto','auto',25.00,12.00,1.50,40.00,3,'Comfortable auto-rickshaw rides'),
('Flash Cab','cab','cab',50.00,18.00,2.00,80.00,4,'Premium cab rides with AC'),
('Flash Premium','cab','premium',80.00,25.00,3.00,120.00,4,'Luxury sedans for premium travel'),
('Flash Pool','cab','pool',30.00,10.00,1.20,50.00,4,'Shared rides to save more')");
        $done[] = "✅ Ride categories seeded (5 types)";

        $pdo->exec("INSERT INTO `promo_codes` (code,discount_type,discount_value,min_ride_amount,max_discount,usage_limit,valid_from,valid_until) VALUES
('FLASH50','percentage',50.00,100.00,75.00,1000,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 30 DAY)),
('WELCOME100','fixed',100.00,150.00,100.00,5000,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 60 DAY)),
('RIDE20','percentage',20.00,50.00,40.00,2000,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 15 DAY))");
        $done[] = "✅ Promo codes seeded (FLASH50, WELCOME100, RIDE20)";

        // password = Test@123
        $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $pdo->exec("INSERT INTO `users` (full_name,email,phone,password_hash,wallet_balance,total_rides,is_verified) VALUES
('Arjun Sharma','arjun@example.com','9876543210','{$hash}',250.00,12,1),
('Priya Patel','priya@example.com','9876543211','{$hash}',500.00,8,1),
('Rahul Verma','rahul@example.com','9876543212','{$hash}',100.00,3,1)");
        $done[] = "✅ Sample riders seeded (3 accounts)";

        $pdo->exec("INSERT INTO `drivers` (full_name,email,phone,password_hash,vehicle_type,vehicle_number,vehicle_model,license_number,wallet_balance,total_rides,is_verified,is_available,current_lat,current_lng) VALUES
('Suresh Kumar','suresh@example.com','9123456780','{$hash}','bike','MH12AB1234','Honda Activa','MH0120101234567',1200.00,45,1,1,18.5204,73.8567),
('Mahesh Singh','mahesh@example.com','9123456781','{$hash}','auto','MH12CD5678','Bajaj Auto','MH0120109876543',2400.00,89,1,1,18.5304,73.8467),
('Vikram Rao','vikram@example.com','9123456782','{$hash}','cab','MH12EF9012','Maruti Swift','MH0120101122334',3500.00,120,1,1,18.5104,73.8667)");
        $done[] = "✅ Sample drivers seeded (3 accounts)";

        // password = Admin@123
        $adminHash = password_hash('Admin@123', PASSWORD_BCRYPT, ['cost'=>10]);
        $stmt = $pdo->prepare("INSERT INTO `admins` (username,email,password_hash,role) VALUES (?,?,?,?)");
        $stmt->execute(['superadmin','admin@flashride.com',$adminHash,'superadmin']);
        $done[] = "✅ Admin account created";

        // 6. Update config/database.php with credentials
        $configFile = __DIR__ . '/config/database.php';
        if (file_exists($configFile)) {
            $cfg = file_get_contents($configFile);
            $cfg = preg_replace("/define\('DB_HOST',\s*'[^']*'\)/", "define('DB_HOST', '{$host}')", $cfg);
            $cfg = preg_replace("/define\('DB_USER',\s*'[^']*'\)/", "define('DB_USER', '{$user}')", $cfg);
            $cfg = preg_replace("/define\('DB_PASS',\s*'[^']*'\)/", "define('DB_PASS', '{$pass}')", $cfg);
            $cfg = preg_replace("/define\('DB_PORT',\s*\d+\)/",      "define('DB_PORT', {$port})",    $cfg);
            file_put_contents($configFile, $cfg);
            $done[] = "✅ <strong>config/database.php</strong> updated with your credentials";
        }

        $done[] = '<hr style="border-color:rgba(255,255,255,.1);margin:.5rem 0;">';
        $done[] = '🎉 <strong>Installation complete!</strong>';

    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>FlashRide Installer</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0A0A0F;color:#F0F0FF;font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
.box{background:#1A1A26;border:1.5px solid rgba(255,255,255,.08);border-radius:18px;padding:2.5rem;width:100%;max-width:540px;box-shadow:0 20px 60px rgba(0,0,0,.5)}
.logo{font-family:'Syne',sans-serif;font-size:2.2rem;font-weight:800;margin-bottom:.2rem}.logo span{color:#FF6B00}
.sub{color:#9898B8;font-size:.9rem;margin-bottom:2rem}
label{display:block;font-size:.8rem;font-weight:600;color:#9898B8;margin:.9rem 0 .3rem;text-transform:uppercase;letter-spacing:.04em}
input{width:100%;padding:.75rem 1rem;background:#12121A;border:1.5px solid rgba(255,255,255,.08);border-radius:8px;color:#F0F0FF;font-size:.95rem;font-family:'DM Sans',sans-serif}
input:focus{outline:none;border-color:#FF6B00;box-shadow:0 0 0 3px rgba(255,107,0,.15)}
.hint{font-size:.75rem;color:#5A5A78;margin-top:.25rem}
.btn{width:100%;margin-top:1.5rem;padding:1rem;background:linear-gradient(135deg,#FF6B00,#e55d00);color:#fff;border:none;border-radius:50px;font-size:1rem;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .2s}
.btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,107,0,.4)}
.btn:active{transform:translateY(0)}
.log{background:#0D0D18;border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:1.2rem;margin-top:1.5rem;font-size:.84rem;line-height:2}
.log .ok{color:#00E676}
.log .err{color:#FF3B6B;font-weight:600}
.links{display:flex;gap:.8rem;margin-top:1.5rem;flex-wrap:wrap}
.links a{flex:1;padding:.75rem;background:#252535;color:#F0F0FF;border-radius:10px;text-align:center;font-weight:600;font-size:.88rem;text-decoration:none;transition:background .2s}
.links a:hover{background:#FF6B00}
.links a.secondary{background:#1C1C28;border:1px solid rgba(255,255,255,.08)}
.note{font-size:.75rem;color:#5A5A78;text-align:center;margin-top:1rem}
.creds{background:#111120;border:1px solid rgba(255,107,0,.2);border-radius:10px;padding:1rem;margin-top:1rem;font-size:.83rem}
.creds h4{color:#FF6B00;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.6rem}
.creds table{width:100%;border-collapse:collapse}
.creds td{padding:.2rem .4rem;color:#9898B8}
.creds td:first-child{color:#F0F0FF;font-weight:600;width:70px}
.creds td code{background:rgba(255,107,0,.12);color:#FF6B00;padding:.1rem .35rem;border-radius:4px;font-family:monospace}
</style>
</head>
<body>
<div class="box">
  <div class="logo"><span>Flash</span>Ride</div>
  <div class="sub">⚙️ One-Click Database Installer</div>

  <?php if (!empty($errors)): ?>
  <div class="log">
    <?php foreach ($errors as $e): ?>
    <div class="err">❌ <?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
    <div style="margin-top:.5rem;font-size:.8rem;color:#9898B8;">
      💡 Common fixes:<br>
      • Wrong password → try leaving password blank<br>
      • Connection refused → make sure XAMPP MySQL is running<br>
      • Access denied → check your MySQL username
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($done)): ?>
  <div class="log">
    <?php foreach ($done as $d): echo "<div class='ok'>{$d}</div>"; endforeach; ?>
  </div>
  <div class="creds">
    <h4>🔑 Login Credentials</h4>
    <table>
      <tr><td>Rider</td><td><code>arjun@example.com</code> / <code>Test@123</code></td></tr>
      <tr><td>Driver</td><td><code>suresh@example.com</code> / <code>Test@123</code></td></tr>
      <tr><td>Admin</td><td><code>admin@flashride.com</code> / <code>Admin@123</code></td></tr>
    </table>
  </div>
  <div class="links">
    <a href="http://<?= $_SERVER['HTTP_HOST'] ?>/flashride/">🚀 Open FlashRide</a>
    <a href="http://<?= $_SERVER['HTTP_HOST'] ?>/flashride/pages/user_login.php" class="secondary">Rider Login</a>
    <a href="http://<?= $_SERVER['HTTP_HOST'] ?>/flashride/admin/login.php" class="secondary">Admin</a>
  </div>
  <p class="note">⚠️ Delete <code>install.php</code> before going live in production</p>

  <?php else: ?>

  <form method="POST">
    <label>MySQL Host</label>
    <input type="text" name="host" value="<?= htmlspecialchars($host) ?>" required>

    <label>MySQL Username</label>
    <input type="text" name="user" value="<?= htmlspecialchars($user) ?>" required>

    <label>MySQL Password</label>
    <input type="password" name="pass" value="" placeholder="Leave blank if XAMPP default (no password)">
    <div class="hint">XAMPP default: root with no password</div>

    <label>MySQL Port</label>
    <input type="number" name="port" value="<?= $port ?>">

    <button type="submit" class="btn">🚀 Install Database Now</button>
  </form>
  <p class="note" style="margin-top:1rem;">Creates <strong>flashride_db</strong> with all 10 tables + sample data</p>

  <?php endif; ?>
</div>
</body>
</html>
