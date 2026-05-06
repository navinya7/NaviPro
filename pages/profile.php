<?php
$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireUserLogin();

$db     = Database::getInstance();
$userId = (int) $_SESSION['user_id'];
$user   = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]) ?? ['full_name'=>'','email'=>'','phone'=>'','wallet_balance'=>0,'rating'=>5,'created_at'=>date('Y-m-d H:i:s'),'password_hash'=>''];
$stats  = $db->fetch(
    "SELECT COUNT(*) as total,
            SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled,
            COALESCE(SUM(CASE WHEN status='completed' THEN final_fare ELSE 0 END),0) as spent
     FROM rides WHERE user_id = ?",
    [$userId]
);

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name  = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email']      ?? '');
        $phone = sanitize($_POST['phone']      ?? '');
        if (!$name || !$email || !$phone) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } else {
            try {
                $existing = $db->fetch(
                    "SELECT id FROM users WHERE (email = ? OR phone = ?) AND id != ?",
                    [$email, $phone, $userId]
                );
                if ($existing) {
                    $error = 'Email or phone already used by another account.';
                } else {
                    $db->execute(
                        "UPDATE users SET full_name=?, email=?, phone=? WHERE id=?",
                        [$name, $email, $phone, $userId]
                    );
                    $_SESSION['user_name'] = $name;
                    $success = 'Profile updated successfully!';
                    $user    = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]) ?? $user;
                }
            } catch (Exception $e) {
                $error = 'Update failed: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $curr = $_POST['current_password']  ?? '';
        $new  = $_POST['new_password']       ?? '';
        $conf = $_POST['confirm_password']   ?? '';
        if (!password_verify($curr, $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $conf) {
            $error = 'New passwords do not match.';
        } else {
            $db->execute(
                "UPDATE users SET password_hash=? WHERE id=?",
                [password_hash($new, PASSWORD_BCRYPT, ['cost' => 10]), $userId]
            );
            $success = 'Password changed successfully!';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<nav class="navbar">
  <a href="<?= APP_URL ?>" class="navbar-brand"><div class="bolt"></div><span><span class="flash">Flash</span>Ride</span></a>
  <div class="nav-links">
    <a href="<?= APP_URL ?>/pages/dashboard.php">Dashboard</a>
    <a href="<?= APP_URL ?>/pages/logout.php" class="nav-btn outline">Logout</a>
  </div>
</nav>

<div style="padding:88px 1.5rem 2rem;max-width:800px;margin:0 auto;">
  <div class="page-header"><h2>My Profile</h2></div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="profile-header fade-in">
    <div class="profile-avatar"><i class="fas fa-user"></i></div>
    <div style="flex:1;">
      <h2><?= htmlspecialchars($user['full_name']) ?></h2>
      <div class="profile-meta">
        <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></span>
        <span><i class="fas fa-phone"></i> <?= htmlspecialchars($user['phone']) ?></span>
        <span><i class="fas fa-star" style="color:var(--warning)"></i> <?= number_format((float)$user['rating'],1) ?> rating</span>
        <span><i class="fas fa-calendar"></i> Since <?= date('M Y', strtotime($user['created_at'])) ?></span>
      </div>
    </div>
  </div>

  <div class="stats-grid fade-in">
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-route"></i></div><div class="stat-num"><?= (int)($stats['total'] ?? 0) ?></div><div class="stat-lbl">Total Rides</div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-num" style="color:var(--success)"><?= (int)($stats['completed'] ?? 0) ?></div><div class="stat-lbl">Completed</div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-times-circle"></i></div><div class="stat-num" style="color:var(--danger)"><?= (int)($stats['cancelled'] ?? 0) ?></div><div class="stat-lbl">Cancelled</div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-rupee-sign"></i></div><div class="stat-num" style="color:var(--primary)">₹<?= number_format((float)($stats['spent'] ?? 0), 0) ?></div><div class="stat-lbl">Total Spent</div></div>
  </div>

  <div class="tabs fade-in">
    <button class="tab-btn active" data-tab="personal">Personal Info</button>
    <button class="tab-btn" data-tab="security">Change Password</button>
  </div>

  <div data-tab-content="personal" class="card fade-in">
    <h4 style="margin-bottom:1.2rem;">Personal Information</h4>
    <form method="POST" autocomplete="off">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group" style="grid-column:1/-1;">
          <label class="form-label">Full Name</label>
          <div class="input-group">
            <i class="fas fa-user input-icon"></i>
            <input type="text" name="full_name" class="form-control" required
                   value="<?= htmlspecialchars($user['full_name']) ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-group">
            <i class="fas fa-envelope input-icon"></i>
            <input type="email" name="email" class="form-control" required
                   value="<?= htmlspecialchars($user['email']) ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <div class="input-group">
            <i class="fas fa-phone input-icon"></i>
            <input type="tel" name="phone" class="form-control" maxlength="10" required
                   value="<?= htmlspecialchars($user['phone']) ?>">
          </div>
        </div>
      </div>
      <button type="submit" name="update_profile" class="btn btn-primary">
        <i class="fas fa-save"></i> Save Changes
      </button>
    </form>
  </div>

  <div data-tab-content="security" class="card fade-in hidden">
    <h4 style="margin-bottom:1.2rem;">Change Password</h4>
    <form method="POST" autocomplete="new-password">
      <div class="form-group">
        <label class="form-label">Current Password</label>
        <div class="input-group">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" name="current_password" class="form-control" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">New Password</label>
        <div class="input-group">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" name="new_password" class="form-control" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm New Password</label>
        <div class="input-group">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
      </div>
      <button type="submit" name="change_password" class="btn btn-primary">
        <i class="fas fa-key"></i> Change Password
      </button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
