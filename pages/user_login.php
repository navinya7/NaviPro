<?php
$pageTitle = 'Login';
require_once __DIR__ . '/../includes/functions.php';
startSession();

if (isUserLoggedIn()) {
    header('Location: ' . APP_URL . '/pages/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = sanitize($_POST['login']    ?? '');
    $pass  = $_POST['password']           ?? '';

    if (!$login || !$pass) {
        $error = 'Please enter your email/phone and password.';
    } else {
        try {
            $db   = Database::getInstance();
            $user = $db->fetch(
                "SELECT * FROM users WHERE (email = ? OR phone = ?) AND status = 'active'",
                [$login, $login]
            );
            if ($user && password_verify($pass, $user['password_hash'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_type'] = 'user';
                $_SESSION['user_name'] = $user['full_name'];
                $db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : APP_URL . '/pages/dashboard.php';
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = 'Invalid email/phone or password. Please try again.';
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
      <p style="color:var(--text-muted);font-size:.85rem;margin-top:.3rem;">Welcome back! Log in to your account</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <div class="tabs">
      <button class="tab-btn active">🏍️ Rider</button>
      <button class="tab-btn" onclick="window.location='<?= APP_URL ?>/pages/driver_login.php'">🚗 Driver</button>
      <button class="tab-btn" onclick="window.location='<?= APP_URL ?>/admin/login.php'">🔐 Admin</button>
    </div>

    <form method="POST" autocomplete="on">
      <div class="form-group">
        <label class="form-label">Email or Phone</label>
        <div class="input-group">
          <i class="fas fa-user input-icon"></i>
          <input type="text" name="login" class="form-control"
                 placeholder="Email or 10-digit phone" required autofocus
                 value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" style="display:flex;justify-content:space-between;">
          <span>Password</span>
          <a href="#" style="font-size:.8rem;">Forgot Password?</a>
        </label>
        <div class="input-group" style="position:relative;">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" name="password" id="loginPass"
                 class="form-control" placeholder="Your password" required>
          <button type="button" id="lpToggle" onclick="togglePass('loginPass','lpToggle')"
                  style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">
        <i class="fas fa-sign-in-alt"></i> Log In
      </button>
    </form>

    <div class="toggle-auth" style="margin-top:1.2rem;">
      Don't have an account? <a href="<?= APP_URL ?>/pages/user_register.php">Sign Up Free</a>
    </div>

    <div style="margin-top:1.5rem;padding:1rem;background:var(--dark-3);border-radius:var(--radius-sm);font-size:.82rem;color:var(--text-muted);">
      <strong style="color:var(--text-secondary);">Demo Credentials:</strong><br>
      Email: <code style="color:var(--primary)">arjun@example.com</code><br>
      Password: <code style="color:var(--primary)">Test@123</code>
    </div>
  </div>
</div>
<script>
function togglePass(inputId, btnId) {
  var input = document.getElementById(inputId);
  var btn   = document.getElementById(btnId);
  if (!input || !btn) return;
  if (input.type === 'password') {
    input.type = 'text';
    btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
  } else {
    input.type = 'password';
    btn.innerHTML = '<i class="fas fa-eye"></i>';
  }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
