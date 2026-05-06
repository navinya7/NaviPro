<?php
$pageTitle = 'Driver Dashboard';
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireDriverLogin();

$db       = Database::getInstance();
$driverId = (int)$_SESSION['user_id'];
$driver   = $db->fetch("SELECT * FROM drivers WHERE id = ?", [$driverId]) ?? ['full_name'=>'Driver','vehicle_type'=>'bike','vehicle_number'=>'','is_available'=>0,'rating'=>5,'wallet_balance'=>0,'total_rides'=>0];

// Toggle online/offline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_availability'])) {
    $new = $driver['is_available'] ? 0 : 1;
    $db->execute("UPDATE drivers SET is_available = ? WHERE id = ?", [$new, $driverId]);
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// Active ride this driver is on
$activeRide = $db->fetch(
    "SELECT r.*, u.full_name as user_name, u.phone as user_phone
     FROM rides r
     JOIN users u ON r.user_id = u.id
     WHERE r.driver_id = ? AND r.status NOT IN ('completed','cancelled')
     ORDER BY r.requested_at DESC LIMIT 1",
    [$driverId]
);

// Next pending ride matching this driver's vehicle type — excluding rides already taken
$pendingRide = null;
if (!$activeRide && $driver['is_available']) {
    $pendingRide = $db->fetch(
        "SELECT r.*, u.full_name as user_name, u.phone as user_phone, rc.name as category_name
         FROM rides r
         JOIN users u  ON r.user_id      = u.id
         JOIN ride_categories rc ON r.category_id = rc.id
         WHERE r.status = 'searching'
           AND r.driver_id IS NULL
           AND rc.type = ?
         ORDER BY r.requested_at ASC
         LIMIT 1",
        [$driver['vehicle_type']]
    );
}

// Stats
$todayEarnings = (float)$db->fetch(
    "SELECT COALESCE(SUM(final_fare),0) as t FROM rides
     WHERE driver_id = ? AND status = 'completed' AND DATE(completed_at) = CURDATE()",
    [$driverId])['t'];
$weekEarnings  = (float)$db->fetch(
    "SELECT COALESCE(SUM(final_fare),0) as t FROM rides
     WHERE driver_id = ? AND status = 'completed'
       AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    [$driverId])['t'];
$todayCount    = (int)$db->fetch(
    "SELECT COUNT(*) as c FROM rides
     WHERE driver_id = ? AND status = 'completed' AND DATE(completed_at) = CURDATE()",
    [$driverId])['c'];
$recentRides   = $db->fetchAll(
    "SELECT r.*, u.full_name as user_name FROM rides r
     JOIN users u ON r.user_id = u.id
     WHERE r.driver_id = ? ORDER BY r.requested_at DESC LIMIT 6",
    [$driverId]
);

require_once __DIR__ . '/../includes/header.php';
?>
<nav class="navbar">
  <a href="<?= APP_URL ?>" class="navbar-brand">
    <div class="bolt"></div><span><span class="flash">Flash</span>Ride</span>
  </a>
  <div class="nav-links">
    <span style="font-size:.85rem;color:var(--text-secondary);">
      <?= htmlspecialchars($driver['full_name']) ?> &nbsp;·&nbsp;
      <?= ['bike'=>'🏍️','auto'=>'🛺','cab'=>'🚗'][$driver['vehicle_type']] ?? '' ?>
      <?= htmlspecialchars($driver['vehicle_number']) ?>
    </span>
    <form method="POST" style="display:inline;">
      <button type="submit" name="toggle_availability"
              class="nav-btn <?= $driver['is_available'] ? '' : 'outline' ?>">
        <?= $driver['is_available'] ? '🟢 Online' : '🔴 Go Online' ?>
      </button>
    </form>
    <a href="<?= APP_URL ?>/pages/driver_logout.php" class="nav-btn outline">Logout</a>
  </div>
</nav>

<div id="toast-container"></div>

<div style="padding:88px 1.5rem 2rem; max-width:1100px; margin:0 auto;">

  <!-- ── Stats ── -->
  <div class="stats-grid fade-in">
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
      <div class="stat-num" style="color:var(--success)">₹<?= number_format($todayEarnings,0) ?></div>
      <div class="stat-lbl">Today's Earnings</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
      <div class="stat-num">₹<?= number_format($weekEarnings,0) ?></div>
      <div class="stat-lbl">This Week</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-route"></i></div>
      <div class="stat-num"><?= $todayCount ?></div>
      <div class="stat-lbl">Rides Today</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-star"></i></div>
      <div class="stat-num" style="color:var(--warning)"><?= number_format((float)$driver['rating'],1) ?>★</div>
      <div class="stat-lbl">Your Rating</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-wallet"></i></div>
      <div class="stat-num" style="color:var(--primary)">₹<?= number_format((float)$driver['wallet_balance'],0) ?></div>
      <div class="stat-lbl">Wallet</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start;">

    <!-- ── LEFT: Active / Pending / Waiting ── -->
    <div>

      <?php if ($activeRide): ?>
      <!-- ACTIVE RIDE CARD -->
      <div class="ride-status-card fade-in mb-3">
        <div class="flex-between mb-3">
          <h3>🚀 Active Ride</h3>
          <span class="badge badge-<?= getStatusBadge($activeRide['status']) ?>">
            <?= ucfirst($activeRide['status']) ?>
          </span>
        </div>

        <!-- Route -->
        <div style="background:var(--dark-3);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1rem;">
          <div style="display:flex;gap:.8rem;margin-bottom:.6rem;align-items:flex-start;">
            <span style="color:var(--success);font-size:1.1rem;flex-shrink:0;">●</span>
            <span style="font-size:.85rem;"><?= htmlspecialchars($activeRide['pickup_address']) ?></span>
          </div>
          <div style="border-left:2px dashed var(--border);height:12px;margin-left:7px;"></div>
          <div style="display:flex;gap:.8rem;align-items:flex-start;">
            <span style="color:var(--primary);font-size:1.1rem;flex-shrink:0;">●</span>
            <span style="font-size:.85rem;"><?= htmlspecialchars($activeRide['dropoff_address']) ?></span>
          </div>
        </div>

        <!-- Rider info -->
        <div class="driver-card" style="background:var(--dark-3);padding:1rem;border-radius:var(--radius);margin-bottom:1rem;">
          <div class="driver-avatar"><i class="fas fa-user"></i></div>
          <div class="driver-info">
            <div class="driver-name"><?= htmlspecialchars($activeRide['user_name']) ?></div>
            <div class="driver-vehicle">Ride <?= htmlspecialchars($activeRide['ride_code']) ?></div>
          </div>
          <div>
            <div style="font-weight:800;color:var(--primary);font-size:1.1rem;">
              <?= formatCurrency((float)$activeRide['estimated_fare']) ?>
            </div>
            <div style="font-size:.75rem;color:var(--text-muted);"><?= ucfirst($activeRide['payment_method']) ?></div>
          </div>
        </div>

        <!-- Action buttons based on status -->
        <div style="display:grid;gap:.7rem;">

          <?php if ($activeRide['status'] === 'accepted'): ?>
            <a href="<?= APP_URL ?>/api/driver_action.php?action=arrived&id=<?= $activeRide['id'] ?>"
               class="btn btn-primary btn-lg">
              <i class="fas fa-map-marker-alt"></i> I've Arrived at Pickup
            </a>
            <a href="tel:<?= htmlspecialchars($activeRide['user_phone']) ?>" class="btn btn-ghost">
              <i class="fas fa-phone"></i> Call Rider (<?= htmlspecialchars($activeRide['user_phone']) ?>)
            </a>

          <?php elseif ($activeRide['status'] === 'arrived'): ?>
            <div style="background:var(--dark-3);border-radius:var(--radius-sm);padding:1rem;text-align:center;margin-bottom:.5rem;">
              <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:.5rem;">
                Ask the rider for their OTP to start the trip
              </p>
            </div>
            <div style="display:flex;gap:.6rem;">
              <input type="text" id="otpInput" class="form-control"
                     placeholder="Enter 4-digit OTP" maxlength="4"
                     style="text-align:center;font-size:1.4rem;font-weight:800;letter-spacing:.3em;">
              <button onclick="startRide(<?= $activeRide['id'] ?>)" class="btn btn-primary" style="white-space:nowrap;">
                <i class="fas fa-play"></i> Start
              </button>
            </div>
            <a href="tel:<?= htmlspecialchars($activeRide['user_phone']) ?>" class="btn btn-ghost">
              <i class="fas fa-phone"></i> Call Rider
            </a>

          <?php elseif ($activeRide['status'] === 'started'): ?>
            <div style="background:var(--success-bg);border:1px solid var(--success);border-radius:var(--radius-sm);padding:.8rem 1rem;text-align:center;font-size:.88rem;color:var(--success);margin-bottom:.3rem;">
              <i class="fas fa-circle" style="animation:pulse 1s infinite;margin-right:.4rem;"></i>
              Trip in progress — drive safely!
            </div>
            <a href="<?= APP_URL ?>/api/driver_action.php?action=complete&id=<?= $activeRide['id'] ?>"
               class="btn btn-success btn-lg"
               onclick="return confirm('Mark this ride as completed?')">
              <i class="fas fa-check-circle"></i> Complete Ride
            </a>
            <button onclick="sendSOS(<?= $activeRide['id'] ?>)" class="sos-btn" style="width:100%;justify-content:center;">
              <i class="fas fa-exclamation-triangle"></i> SOS Emergency
            </button>
          <?php endif; ?>

        </div>

        <!-- Live map -->
        <div id="driverMap" style="height:220px;border-radius:var(--radius);margin-top:1rem;border:1px solid var(--border);"></div>
      </div>

      <?php elseif ($pendingRide): ?>
      <!-- NEW RIDE REQUEST -->
      <div class="ride-status-card fade-in" style="border-color:var(--warning);box-shadow:0 0 30px rgba(255,214,0,0.15);" id="rideRequestCard">
        <div class="flex-between mb-3">
          <h3>🔔 New Ride Request!</h3>
          <span class="badge badge-warning">Waiting for you</span>
        </div>

        <div style="background:var(--dark-3);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1rem;">
          <div style="display:flex;gap:.8rem;margin-bottom:.6rem;align-items:flex-start;">
            <span style="color:var(--success);font-size:1.1rem;flex-shrink:0;">●</span>
            <span style="font-size:.85rem;"><?= htmlspecialchars($pendingRide['pickup_address']) ?></span>
          </div>
          <div style="border-left:2px dashed var(--border);height:12px;margin-left:7px;"></div>
          <div style="display:flex;gap:.8rem;align-items:flex-start;">
            <span style="color:var(--primary);font-size:1.1rem;flex-shrink:0;">●</span>
            <span style="font-size:.85rem;"><?= htmlspecialchars($pendingRide['dropoff_address']) ?></span>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.8rem;margin-bottom:1.2rem;">
          <div style="background:var(--dark-3);border-radius:var(--radius-sm);padding:.8rem;text-align:center;">
            <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:.2rem;">RIDER</div>
            <div style="font-weight:700;font-size:.9rem;"><?= htmlspecialchars($pendingRide['user_name']) ?></div>
          </div>
          <div style="background:var(--dark-3);border-radius:var(--radius-sm);padding:.8rem;text-align:center;">
            <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:.2rem;">DISTANCE</div>
            <div style="font-weight:700;font-size:.9rem;"><?= number_format((float)$pendingRide['distance_km'],1) ?> km</div>
          </div>
          <div style="background:var(--dark-3);border-radius:var(--radius-sm);padding:.8rem;text-align:center;">
            <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:.2rem;">FARE</div>
            <div style="font-weight:800;font-size:1rem;color:var(--primary);"><?= formatCurrency((float)$pendingRide['estimated_fare']) ?></div>
          </div>
        </div>

        <!-- Accept / Reject -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
          <a href="<?= APP_URL ?>/api/driver_action.php?action=accept&id=<?= $pendingRide['id'] ?>"
             class="btn btn-success btn-lg" style="justify-content:center;">
            <i class="fas fa-check"></i> Accept Ride
          </a>
          <a href="<?= APP_URL ?>/api/driver_action.php?action=reject&id=<?= $pendingRide['id'] ?>"
             class="btn btn-ghost btn-lg" style="justify-content:center;">
            <i class="fas fa-times"></i> Skip
          </a>
        </div>

        <div style="margin-top:.8rem;font-size:.78rem;color:var(--text-muted);text-align:center;">
          Page refreshes automatically every 8 seconds
        </div>
      </div>

      <?php else: ?>
      <!-- WAITING / OFFLINE -->
      <div class="card fade-in" style="text-align:center;padding:3rem 2rem;">
        <?php if (!$driver['is_available']): ?>
          <div style="font-size:3.5rem;margin-bottom:1rem;">😴</div>
          <h3>You're Offline</h3>
          <p style="color:var(--text-muted);margin:.8rem 0 1.5rem;">Go online to receive ride requests from riders near you.</p>
          <form method="POST">
            <button type="submit" name="toggle_availability" class="btn btn-primary btn-lg">
              <i class="fas fa-power-off"></i> Go Online Now
            </button>
          </form>
        <?php else: ?>
          <div class="spinner" style="margin:0 auto 1.5rem;width:52px;height:52px;border-width:4px;"></div>
          <h3>Waiting for Rides</h3>
          <p style="color:var(--text-muted);margin:.5rem 0 1rem;">You're online. This page checks for new ride requests automatically.</p>
          <p style="font-size:.82rem;color:var(--text-muted);">
            Next check in <span id="refreshCountdown" style="color:var(--primary);font-weight:700;">8</span>s
          </p>
          <div style="margin-top:1.5rem;padding:1rem;background:var(--dark-3);border-radius:var(--radius-sm);font-size:.82rem;color:var(--text-muted);text-align:left;">
            <strong style="color:var(--text-secondary);">💡 How to test with your own rider account:</strong><br><br>
            1. Open a <strong>new Incognito window</strong> (Ctrl+Shift+N)<br>
            2. Log in as a rider at <code style="color:var(--primary);">/pages/user_login.php</code><br>
            3. Book a ride — it will appear here instantly!<br>
            4. Click <strong>Accept Ride</strong> to be your own driver 🎉
          </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div>

    <!-- ── RIGHT: Recent rides ── -->
    <div class="card fade-in">
      <div class="card-header flex-between">
        <h4 class="card-title">Recent Rides</h4>
        <span style="font-size:.78rem;color:var(--text-muted);">Total: <?= $driver['total_rides'] ?></span>
      </div>
      <?php if (empty($recentRides)): ?>
        <p style="color:var(--text-muted);padding:1rem 0;font-size:.88rem;">No completed rides yet.</p>
      <?php else: foreach ($recentRides as $r): ?>
      <div style="display:flex;align-items:center;gap:.8rem;padding:.8rem 0;border-bottom:1px solid var(--border);">
        <div style="width:36px;height:36px;border-radius:8px;background:var(--primary-glow);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="fas fa-route" style="color:var(--primary);font-size:.8rem;"></i>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <?= htmlspecialchars($r['user_name']) ?>
          </div>
          <div style="font-size:.72rem;color:var(--text-muted);">
            <?= date('d M, H:i', strtotime($r['requested_at'])) ?>
          </div>
        </div>
        <div style="text-align:right;flex-shrink:0;">
          <div style="font-weight:700;font-size:.88rem;color:<?= $r['status']==='completed'?'var(--success)':'var(--text-muted)' ?>">
            <?= $r['status']==='completed' ? '+'.formatCurrency((float)($r['final_fare'] ?? $r['estimated_fare'])) : '—' ?>
          </div>
          <span class="badge badge-<?= getStatusBadge($r['status']) ?>" style="font-size:.6rem;">
            <?= ucfirst($r['status']) ?>
          </span>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Show toast message after redirect (e.g. after accepting a ride)
  (function(){
    var params = new URLSearchParams(window.location.search);
    var msg  = params.get('toast');
    var type = params.get('type') || 'info';
    if (msg) {
      setTimeout(function(){
        Toast.show(type, type.charAt(0).toUpperCase() + type.slice(1), decodeURIComponent(msg));
      }, 400);
    }
  })();


  // ── Live map for active ride ──
  <?php if ($activeRide): ?>
  var map = MapHelper.initMap('driverMap', <?= $activeRide['pickup_lat'] ?>, <?= $activeRide['pickup_lng'] ?>, 14);
  MapHelper.addMarker(<?= $activeRide['pickup_lat'] ?>, <?= $activeRide['pickup_lng'] ?>, 'pickup', 'Pickup');
  MapHelper.addMarker(<?= $activeRide['dropoff_lat'] ?>, <?= $activeRide['dropoff_lng'] ?>, 'drop', 'Drop');
  MapHelper.drawRoute([
    [<?= $activeRide['pickup_lat'] ?>, <?= $activeRide['pickup_lng'] ?>],
    [<?= $activeRide['dropoff_lat'] ?>, <?= $activeRide['dropoff_lng'] ?>]
  ]);
  // Push driver GPS location every 10s
  function pushLocation() {
    MapHelper.getUserLocation(function(loc) {
      API.post('<?= APP_URL ?>/api/update_location.php', { lat: loc.lat, lng: loc.lng });
    });
  }
  pushLocation();
  setInterval(pushLocation, 10000);
  <?php endif; ?>

  // ── Auto-refresh countdown when waiting for rides ──
  <?php if (!$activeRide && $driver['is_available'] && !$pendingRide): ?>
  var countdown = 8;
  var el = document.getElementById('refreshCountdown');
  setInterval(function() {
    countdown--;
    if (el) el.textContent = countdown;
    if (countdown <= 0) {
      window.location.reload();
    }
  }, 1000);
  <?php endif; ?>

  // ── Auto-refresh when there's a pending ride (keep it fresh) ──
  <?php if ($pendingRide): ?>
  setTimeout(function() { window.location.reload(); }, 15000);
  <?php endif; ?>

});

// Start ride with OTP verification
async function startRide(rideId) {
  var otp = document.getElementById('otpInput').value.trim();
  if (!otp || otp.length !== 4) {
    Toast.warning('OTP Required', 'Ask the rider for their 4-digit OTP');
    return;
  }
  var res = await API.post('<?= APP_URL ?>/api/driver_action.php', {
    action: 'start', id: rideId, otp: otp
  });
  if (res.status === 'success') {
    Toast.success('Ride Started!', 'Have a safe trip!');
    setTimeout(function() { window.location.reload(); }, 800);
  } else {
    Toast.error('Wrong OTP', res.message || 'Incorrect OTP, ask the rider again');
    document.getElementById('otpInput').value = '';
    document.getElementById('otpInput').focus();
  }
}

function sendSOS(rideId) {
  if (confirm('🚨 Send emergency SOS alert?')) {
    API.post('<?= APP_URL ?>/api/sos.php', { ride_id: rideId });
    Toast.error('SOS Sent!', 'Emergency services have been notified.');
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
