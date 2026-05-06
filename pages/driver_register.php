<?php
$pageTitle = 'Driver Registration';
require_once __DIR__ . '/../includes/functions.php';
startSession();

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['full_name']      ?? '');
    $email   = sanitize($_POST['email']          ?? '');
    $phone   = sanitize($_POST['phone']          ?? '');
    $vtype   = sanitize($_POST['vehicle_type']   ?? '');
    $vnum    = strtoupper(sanitize($_POST['vehicle_number'] ?? ''));
    $vmodel  = sanitize($_POST['vehicle_model']  ?? '');
    $license = strtoupper(sanitize($_POST['license_number'] ?? ''));
    $pass    = $_POST['password']                ?? '';
    $cpass   = $_POST['confirm_password']        ?? '';

    if (!$name || !$email || !$phone || !$vtype || !$vnum || !$license || !$pass) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $phone)) {
        $error = 'Invalid Indian phone number (10 digits, starting 6-9).';
    } elseif (!in_array($vtype, ['bike', 'auto', 'cab'])) {
        $error = 'Please select a valid vehicle type.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass !== $cpass) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $db       = Database::getInstance();
            $existing = $db->fetch(
                "SELECT id FROM drivers WHERE email = ? OR phone = ?",
                [$email, $phone]
            );
            if ($existing) {
                $error = 'This email or phone is already registered as a driver.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 10]);
                $id   = (int) $db->insert(
                    "INSERT INTO drivers (full_name, email, phone, password_hash, vehicle_type, vehicle_number, vehicle_model, license_number, is_verified) VALUES (?,?,?,?,?,?,?,?,1)",
                    [$name, $email, $phone, $hash, $vtype, $vnum, $vmodel, $license]
                );
                sendNotification('driver', $id, 'Welcome to FlashRide Driver!',
                    "Hi $name! Your driver account is ready. Start accepting rides and earn money!");
                $success = 'Driver account created! Redirecting to login...';
            }
        } catch (Exception $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div id="toast-container"></div>
<div class="auth-page" style="padding-top:5rem;padding-bottom:3rem;">
  <div class="auth-card fade-in" style="max-width:520px;">

    <div class="auth-logo">
      <div class="logo-text"><span>Flash</span>Ride <span style="font-size:1.2rem;">Driver</span></div>
      <p style="color:var(--text-muted);font-size:.85rem;">Join thousands of drivers earning with FlashRide</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
      <script>setTimeout(function(){ window.location.href='<?= APP_URL ?>/pages/driver_login.php'; }, 1500);</script>
    <?php else: ?>

    <form method="POST" autocomplete="off">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">

        <div class="form-group" style="grid-column:1/-1;">
          <label class="form-label">Full Name</label>
          <div class="input-group">
            <i class="fas fa-user input-icon"></i>
            <input type="text" name="full_name" class="form-control"
                   placeholder="Suresh Kumar" required
                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control"
                 placeholder="you@email.com" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="tel" name="phone" class="form-control"
                 placeholder="9876543210" maxlength="10" required
                 value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Vehicle Type</label>
          <select name="vehicle_type" class="form-control" required>
            <option value="">— Select —</option>
            <option value="bike" <?= (($_POST['vehicle_type'] ?? '') === 'bike') ? 'selected' : '' ?>>🏍️ Bike</option>
            <option value="auto" <?= (($_POST['vehicle_type'] ?? '') === 'auto') ? 'selected' : '' ?>>🛺 Auto Rickshaw</option>
            <option value="cab"  <?= (($_POST['vehicle_type'] ?? '') === 'cab')  ? 'selected' : '' ?>>🚗 Cab / Car</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Vehicle Number</label>
          <input type="text" name="vehicle_number" class="form-control"
                 placeholder="MH12AB1234" required
                 style="text-transform:uppercase;"
                 value="<?= htmlspecialchars($_POST['vehicle_number'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Vehicle Model</label>
          <input type="text" name="vehicle_model" class="form-control"
                 placeholder="Honda Activa"
                 value="<?= htmlspecialchars($_POST['vehicle_model'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">License Number</label>
          <input type="text" name="license_number" class="form-control"
                 placeholder="MH0120231234567" required
                 style="text-transform:uppercase;"
                 value="<?= htmlspecialchars($_POST['license_number'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control"
                 placeholder="Min 8 characters" required>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="confirm_password" class="form-control"
                 placeholder="Repeat password" required>
        </div>

      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:.5rem;">
        <i class="fas fa-motorcycle"></i> Register as Driver
      </button>
    </form>

    <div class="toggle-auth" style="margin-top:1.2rem;">
      Already a driver? <a href="<?= APP_URL ?>/pages/driver_login.php">Login Here</a>
    </div>
    <div class="auth-divider"><span>Rider?</span></div>
    <a href="<?= APP_URL ?>/pages/user_register.php" class="btn btn-ghost btn-block">
      <i class="fas fa-user"></i> Register as Rider Instead
    </a>

    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
