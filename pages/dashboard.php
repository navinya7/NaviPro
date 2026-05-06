<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireUserLogin();

try {
$db = Database::getInstance();
$userId = (int)$_SESSION['user_id'];
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]) ?? ['full_name'=>'Rider','wallet_balance'=>0,'total_rides'=>0,'rating'=>5];
$recentRides = $db->fetchAll("SELECT r.*, rc.name as category_name, rc.type as vehicle_type, d.full_name as driver_name FROM rides r JOIN ride_categories rc ON r.category_id=rc.id LEFT JOIN drivers d ON r.driver_id=d.id WHERE r.user_id=? ORDER BY r.requested_at DESC LIMIT 5", [$userId]);
$activeRide = $db->fetch("SELECT r.*, rc.name as category_name, d.full_name as driver_name, d.phone as driver_phone, d.vehicle_number, d.vehicle_model FROM rides r JOIN ride_categories rc ON r.category_id=rc.id LEFT JOIN drivers d ON r.driver_id=d.id WHERE r.user_id=? AND r.status NOT IN ('completed','cancelled') ORDER BY r.requested_at DESC LIMIT 1", [$userId]);
$notifications = $db->fetchAll("SELECT * FROM notifications WHERE recipient_type='user' AND recipient_id=? ORDER BY created_at DESC LIMIT 5", [$userId]);
$unreadCount = $db->fetch("SELECT COUNT(*) as cnt FROM notifications WHERE recipient_type='user' AND recipient_id=? AND is_read=0", [$userId])['cnt'] ?? 0;

} catch(Exception $e) { $dbError = $e->getMessage(); }
require_once __DIR__ . '/../includes/header.php';
?>
<nav class="navbar">
  <a href="<?= APP_URL ?>" class="navbar-brand"><div class="bolt"></div><span><span class="flash">Flash</span>Ride</span></a>
  <div class="nav-links">
    <a href="<?= APP_URL ?>/pages/book_ride.php">Book Ride</a>
    <a href="<?= APP_URL ?>/pages/ride_history.php">History</a>
    <a href="<?= APP_URL ?>/pages/wallet.php">Wallet</a>
    <a href="<?= APP_URL ?>/pages/profile.php" class="<?= $unreadCount ? 'notif-badge' : '' ?>"><?= htmlspecialchars($user['full_name']) ?></a>
    <a href="<?= APP_URL ?>/pages/logout.php" class="nav-btn outline">Logout</a>
  </div>
</nav>

<div style="padding:88px 2rem 2rem;max-width:1100px;margin:0 auto;">
  <!-- Welcome + Active Ride -->
  <?php if ($activeRide): ?>
  <div class="ride-status-card fade-in mb-3">
    <div class="flex-between mb-2">
      <div>
        <h3>Active Ride</h3>
        <p style="color:var(--text-muted);font-size:.85rem;"><?= htmlspecialchars($activeRide['ride_code']) ?></p>
      </div>
      <span class="badge badge-<?= getStatusBadge($activeRide['status']) ?>"><?= ucfirst($activeRide['status']) ?></span>
    </div>
    <div id="trackingMapDash" style="height:250px;border-radius:var(--radius);border:1px solid var(--border);margin-bottom:1rem;"></div>
    <?php if ($activeRide['driver_id']): ?>
    <div class="driver-card">
      <div class="driver-avatar"><i class="fas fa-user"></i></div>
      <div class="driver-info">
        <div class="driver-name"><?= htmlspecialchars($activeRide['driver_name']) ?></div>
        <div class="driver-vehicle"><?= htmlspecialchars($activeRide['vehicle_model'] ?? '') ?> · <?= htmlspecialchars($activeRide['vehicle_number'] ?? '') ?></div>
      </div>
      <div class="ride-actions" style="margin-top:0;flex-direction:column;gap:.4rem;">
        <a href="tel:<?= htmlspecialchars($activeRide['driver_phone']) ?>" class="contact-btn btn-sm"><i class="fas fa-phone"></i> Call</a>
      </div>
    </div>
    <?php endif; ?>
    <?php if (in_array($activeRide['status'], ['accepted','arrived','started']) && !empty($activeRide['otp_for_start'])): ?>
    <div style="background:var(--dark-3);border:1px solid var(--border-glow);border-radius:var(--radius);padding:.9rem;text-align:center;margin-top:1rem;">
      <p style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem;">
        <i class="fas fa-key" style="color:var(--primary)"></i> &nbsp;Ride OTP — Show to Driver
      </p>
      <div style="font-size:2.5rem;font-weight:800;letter-spacing:.5em;color:var(--primary);font-family:var(--font-display);">
        <?= htmlspecialchars($activeRide['otp_for_start']) ?>
      </div>
    </div>
    <?php endif; ?>
    <div style="display:flex;gap:.8rem;margin-top:1rem;">
      <a href="<?= APP_URL ?>/pages/track_ride.php?id=<?= $activeRide['id'] ?>" class="btn btn-primary" style="flex:1"><i class="fas fa-map-marked-alt"></i> Track Ride</a>
      <?php if (in_array($activeRide['status'],['searching','accepted','arrived'])): ?>
      <button onclick="showCancelModal(<?= $activeRide['id'] ?>)" class="btn btn-ghost">
        <i class="fas fa-times"></i> Cancel Ride
      </button>
      <?php endif; ?>
    </div>
  </div>
  <?php else: ?>
  <div class="card fade-in mb-3" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));border:none;padding:2rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
      <div>
        <h2 style="color:#fff;">Hello, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>! 👋</h2>
        <p style="color:rgba(255,255,255,.8);margin-top:.3rem;">Ready to ride? Book your next trip now.</p>
      </div>
      <a href="<?= APP_URL ?>/pages/book_ride.php" class="btn" style="background:#fff;color:var(--primary);font-weight:700;"><i class="fas fa-bolt"></i> Book Now</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats-grid fade-in">
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-wallet"></i></div>
      <div class="stat-num" style="color:var(--success)"><?= formatCurrency($user['wallet_balance']) ?></div>
      <div class="stat-lbl">Wallet Balance</div>
      <a href="<?= APP_URL ?>/pages/wallet.php" style="font-size:.8rem;color:var(--primary);">Add Money →</a>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-route"></i></div>
      <div class="stat-num"><?= $user['total_rides'] ?></div>
      <div class="stat-lbl">Total Rides</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-star"></i></div>
      <div class="stat-num" style="color:var(--warning)"><?= number_format($user['rating'],1) ?>★</div>
      <div class="stat-lbl">Your Rating</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-bell"></i></div>
      <div class="stat-num" style="color:var(--info)"><?= $unreadCount ?></div>
      <div class="stat-lbl">Notifications</div>
    </div>
  </div>

  <!-- Recent Rides & Notifications -->
  <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;margin-top:0;" id="dashGrid">
    <div class="card fade-in">
      <div class="card-header flex-between">
        <h4 class="card-title">Recent Rides</h4>
        <a href="<?= APP_URL ?>/pages/ride_history.php" style="font-size:.85rem;color:var(--primary);">View All →</a>
      </div>
      <?php if (empty($recentRides)): ?>
      <div style="text-align:center;padding:2rem;color:var(--text-muted);">
        <i class="fas fa-route" style="font-size:2.5rem;margin-bottom:.8rem;display:block;opacity:.3;"></i>
        No rides yet. <a href="<?= APP_URL ?>/pages/book_ride.php">Book your first ride!</a>
      </div>
      <?php else: foreach ($recentRides as $ride): ?>
      <div style="display:flex;align-items:center;gap:1rem;padding:.9rem 0;border-bottom:1px solid var(--border);">
        <div style="width:40px;height:40px;border-radius:10px;background:var(--primary-glow);display:flex;align-items:center;justify-content:center;color:var(--primary);flex-shrink:0;">
          <?= $ride['vehicle_type']==='bike'?'🏍️':($ride['vehicle_type']==='auto'?'🛺':'🚗') ?>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:600;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars(substr($ride['dropoff_address'],0,45)) ?>...</div>
          <div style="font-size:.78rem;color:var(--text-muted);"><?= htmlspecialchars($ride['category_name']) ?> · <?= date('d M', strtotime($ride['requested_at'])) ?></div>
        </div>
        <div style="text-align:right;flex-shrink:0;">
          <div style="font-weight:700;color:var(--primary);"><?= formatCurrency($ride['final_fare'] ?? $ride['estimated_fare']) ?></div>
          <span class="badge badge-<?= getStatusBadge($ride['status']) ?>"><?= ucfirst($ride['status']) ?></span>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>

    <div class="card fade-in">
      <div class="card-header"><h4 class="card-title">Notifications</h4></div>
      <?php if (empty($notifications)): ?>
      <p style="color:var(--text-muted);font-size:.88rem;">No notifications.</p>
      <?php else: foreach ($notifications as $n): ?>
      <div style="padding:.8rem 0;border-bottom:1px solid var(--border);<?= $n['is_read']?'opacity:.6':'' ?>">
        <div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($n['title']) ?></div>
        <div style="font-size:.78rem;color:var(--text-muted);margin-top:.2rem;"><?= htmlspecialchars(substr($n['message'],0,80)) ?>...</div>
        <div style="font-size:.72rem;color:var(--text-muted);margin-top:.3rem;"><?= timeAgo($n['created_at']) ?></div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  <?php if ($activeRide): ?>
  const map = MapHelper.initMap('trackingMapDash', <?= $activeRide['pickup_lat'] ?>, <?= $activeRide['pickup_lng'] ?>, 13);
  MapHelper.addMarker(<?= $activeRide['pickup_lat'] ?>, <?= $activeRide['pickup_lng'] ?>, 'pickup', 'Pickup');
  MapHelper.addMarker(<?= $activeRide['dropoff_lat'] ?>, <?= $activeRide['dropoff_lng'] ?>, 'drop', 'Drop');
  MapHelper.drawRoute([[<?= $activeRide['pickup_lat'] ?>,<?= $activeRide['pickup_lng'] ?>],[<?= $activeRide['dropoff_lat'] ?>,<?= $activeRide['dropoff_lng'] ?>]]);
  <?php endif; ?>
});
</script>


<!-- ══ CANCEL RIDE MODAL ══════════════════════════════════════ -->
<div class="modal-overlay" id="cancelModal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-times-circle" style="color:var(--danger)"></i> Cancel Ride</h3>
      <button class="modal-close" onclick="Modal.close('cancelModal')">&times;</button>
    </div>
    <p style="color:var(--text-secondary);margin-bottom:1.2rem;font-size:.9rem;">
      Please select a reason for cancellation:
    </p>
    <div id="cancelReasons" style="display:flex;flex-direction:column;gap:.6rem;margin-bottom:1.5rem;">
      <?php
      $reasons = [
        'Driver is taking too long',
        'Wrong pickup location entered',
        'I found another ride',
        'Emergency / Change of plans',
        'Driver asked to cancel',
        'Other reason',
      ];
      foreach ($reasons as $r): ?>
      <label style="display:flex;align-items:center;gap:.8rem;padding:.75rem 1rem;background:var(--dark-3);border-radius:var(--radius-sm);cursor:pointer;border:1.5px solid transparent;transition:all .2s;"
             onmouseover="this.style.borderColor='var(--primary)'"
             onmouseout="if(!this.querySelector('input').checked)this.style.borderColor='transparent'"
             onclick="this.style.borderColor='var(--primary)'">
        <input type="radio" name="cancelReason" value="<?= htmlspecialchars($r) ?>"
               style="accent-color:var(--primary);width:16px;height:16px;">
        <span style="font-size:.88rem;"><?= htmlspecialchars($r) ?></span>
      </label>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;gap:.8rem;">
      <button onclick="Modal.close('cancelModal')" class="btn btn-ghost" style="flex:1">
        Keep Ride
      </button>
      <button onclick="confirmCancel()" class="btn btn-danger" style="flex:1" id="confirmCancelBtn">
        <i class="fas fa-times"></i> Yes, Cancel Ride
      </button>
    </div>
  </div>
</div>

<script>
var rideToCancel = null;

function showCancelModal(rideId) {
  rideToCancel = rideId;
  // Reset radio selection
  document.querySelectorAll('input[name="cancelReason"]').forEach(function(r) {
    r.checked = false;
    r.closest('label').style.borderColor = 'transparent';
  });
  Modal.open('cancelModal');
}

async function confirmCancel() {
  var selected = document.querySelector('input[name="cancelReason"]:checked');
  if (!selected) {
    Toast.warning('Select a reason', 'Please select a reason for cancellation');
    return;
  }
  var btn = document.getElementById('confirmCancelBtn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner" style="width:18px;height:18px;border-width:2px;margin:0 auto;"></div>';

  try {
    var res = await API.post('<?= APP_URL ?>/api/cancel_ride.php', {
      ride_id: rideToCancel,
      reason:  selected.value
    });
    Modal.close('cancelModal');
    if (res.status === 'success') {
      Toast.success('Ride Cancelled', 'Your ride has been cancelled successfully.');
      setTimeout(function() { window.location.reload(); }, 1200);
    } else {
      Toast.error('Error', res.message || 'Could not cancel ride');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-times"></i> Yes, Cancel Ride';
    }
  } catch(e) {
    Toast.error('Error', 'Connection problem. Try again.');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-times"></i> Yes, Cancel Ride';
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
