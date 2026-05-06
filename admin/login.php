<?php
$pageTitle = 'Admin Login';
require_once __DIR__ . '/../includes/functions.php';
startSession();

if (isAdminLoggedIn()) {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']  ?? '');
    $pass  = $_POST['password']         ?? '';

    if (!$email || !$pass) {
        $error = 'Please enter email and password.';
    } else {
        try {
            $db    = Database::getInstance();
            $admin = $db->fetch("SELECT * FROM admins WHERE email = ?", [$email]);
            if ($admin && password_verify($pass, $admin['password_hash'])) {
                $_SESSION['user_id']    = $admin['id'];
                $_SESSION['user_type']  = 'admin';
                $_SESSION['user_name']  = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                header('Location: ' . APP_URL . '/admin/dashboard.php');
                exit;
            } else {
                $error = 'Invalid admin credentials.';
            }
        } catch (Exception $e) {
            $error = 'Login error: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div id="toast-container"></div>
<div class="auth-page">
  <div class="auth-card fade-in">
    <div class="auth-logo">
      <div class="logo-text"><span>Flash</span>Ride</div>
      <p style="color:var(--text-muted);font-size:.85rem;margin-top:.3rem;">Admin Control Panel</p>
    </div>

    <div style="background:var(--warning-bg);border:1px solid var(--warning);border-radius:var(--radius-sm);padding:.8rem 1rem;margin-bottom:1.5rem;display:flex;gap:.6rem;font-size:.82rem;color:var(--warning);">
      <i class="fas fa-shield-alt" style="margin-top:1px;"></i>
      <span>Restricted access — Authorized personnel only</span>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="tabs">
      <button class="tab-btn" onclick="window.location='<?= APP_URL ?>/pages/user_login.php'">🏍️ Rider</button>
      <button class="tab-btn" onclick="window.location='<?= APP_URL ?>/pages/driver_login.php'">🚗 Driver</button>
      <button class="tab-btn active">🔐 Admin</button>
    </div>

    <form method="POST" autocomplete="on">
      <div class="form-group">
        <label class="form-label">Admin Email</label>
        <div class="input-group">
          <i class="fas fa-envelope input-icon"></i>
          <input type="email" name="email" class="form-control"
                 placeholder="admin@flashride.com" required autofocus
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-group">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" name="password" class="form-control"
                 placeholder="Admin password" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">
        <i class="fas fa-sign-in-alt"></i> Admin Login
      </button>
    </form>

    <div style="margin-top:1.5rem;padding:1rem;background:var(--dark-3);border-radius:var(--radius-sm);font-size:.82rem;color:var(--text-muted);">
      <strong style="color:var(--text-secondary);">Demo Admin:</strong><br>
      Email: <code style="color:var(--primary)">admin@flashride.com</code><br>
      Password: <code style="color:var(--primary)">Admin@123</code>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
