<?php
// ============================================================
// FlashRide — Rider Registration
// ============================================================
$pageTitle = 'Create Account';
require_once __DIR__ . '/../includes/functions.php';
startSession();

if (isUserLoggedIn()) {
    header('Location: ' . APP_URL . '/pages/dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = sanitize($_POST['full_name']        ?? '');
    $email = sanitize($_POST['email']             ?? '');
    $phone = sanitize($_POST['phone']             ?? '');
    $pass  = $_POST['password']                   ?? '';
    $cpass = $_POST['confirm_password']           ?? '';

    if (!$name || !$email || !$phone || !$pass) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $phone)) {
        $error = 'Invalid Indian phone number (must be 10 digits starting with 6-9).';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass !== $cpass) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $db       = Database::getInstance();
            $existing = $db->fetch(
                "SELECT id FROM users WHERE email = ? OR phone = ?",
                [$email, $phone]
            );

            if ($existing) {
                $error = 'This email or phone number is already registered.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 10]);
                $id   = (int) $db->insert(
                    "INSERT INTO users (full_name, email, phone, password_hash, is_verified) VALUES (?,?,?,?,1)",
                    [$name, $email, $phone, $hash]
                );

                // Give welcome bonus wallet credit
                creditWallet('user', $id, 100.00, 'Welcome bonus!');

                // Send notification
                sendNotification(
                    'user', $id,
                    'Welcome to FlashRide!',
                    "Hi $name! Your account is ready. You have ₹100 welcome bonus in your wallet."
                );

                $success = 'Account created successfully! Redirecting to login...';
            }
        } catch (Exception $e) {
            $error = 'Registration failed. Please try again. (' . $e->getMessage() . ')';
        }
    }
}

// Include header AFTER all PHP logic (never echo before this)
require_once __DIR__ . '/../includes/header.php';
?>

<div id="toast-container"></div>

<div class="auth-page">
  <div class="auth-card fade-in">

    <div class="auth-logo">
      <div class="logo-text"><span>Flash</span>Ride</div>
      <p style="color:var(--text-muted);font-size:.85rem;margin-top:.3rem;">Create your free rider account</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
      </div>
      <script>
        setTimeout(function() {
          window.location.href = '<?= APP_URL ?>/pages/user_login.php';
        }, 1500);
      </script>
    <?php else: ?>

    <form method="POST" id="registerForm" autocomplete="off">

      <div class="form-group">
        <label class="form-label">Full Name</label>
        <div class="input-group">
          <i class="fas fa-user input-icon"></i>
          <input type="text" name="full_name" class="form-control"
                 placeholder="Arjun Sharma" required
                 value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-group">
          <i class="fas fa-envelope input-icon"></i>
          <input type="email" name="email" class="form-control"
                 placeholder="you@example.com" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <div class="input-group">
          <i class="fas fa-phone input-icon"></i>
          <input type="tel" name="phone" class="form-control"
                 placeholder="9876543210" maxlength="10" required
                 value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
        <div class="form-text">10-digit Indian mobile number</div>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-group" style="position:relative;">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" name="password" id="passInput"
                 class="form-control" placeholder="Min 8 characters" required>
          <button type="button" id="togglePassBtn"
                  onclick="togglePass('passInput','togglePassBtn')"
                  style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
            <i class="fas fa-eye"></i>
          </button>
        </div>
        <div id="passStrength" class="form-text"></div>
      </div>

      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <div class="input-group">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" name="confirm_password" class="form-control"
                 placeholder="Repeat your password" required>
        </div>
      </div>

      <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:1.2rem;">
        By signing up you agree to our <a href="#">Terms</a> &amp; <a href="#">Privacy Policy</a>.
      </p>

      <button type="submit" class="btn btn-primary btn-block btn-lg">
        <i class="fas fa-user-plus"></i> Create Account — Get ₹100 Free
      </button>
    </form>

    <div class="toggle-auth" style="margin-top:1.2rem;">
      Already have an account?
      <a href="<?= APP_URL ?>/pages/user_login.php">Log In</a>
    </div>

    <div class="auth-divider"><span>Want to earn?</span></div>

    <a href="<?= APP_URL ?>/pages/driver_register.php" class="btn btn-ghost btn-block">
      <i class="fas fa-motorcycle"></i> Register as Driver Instead
    </a>

    <?php endif; ?>
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

var passInput = document.getElementById('passInput');
if (passInput) {
  passInput.addEventListener('input', function() {
    var el  = document.getElementById('passStrength');
    var v   = this.value;
    var str = 0;
    if (v.length >= 8)            str++;
    if (/[A-Z]/.test(v))          str++;
    if (/[0-9]/.test(v))          str++;
    if (/[^A-Za-z0-9]/.test(v))   str++;
    var labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    var colors = ['', 'var(--danger)', 'var(--warning)', 'var(--info)', 'var(--success)'];
    if (el) {
      el.textContent = v.length ? 'Strength: ' + labels[str] : '';
      el.style.color = colors[str] || '';
    }
  });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
