<?php
$pageTitle = 'Track Ride';
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireUserLogin();

$rideId = (int)($_GET['id'] ?? 0);
$db = Database::getInstance();
$ride = $db->fetch("SELECT r.*, rc.name as category_name, rc.type as vehicle_type, d.full_name as driver_name, d.phone as driver_phone, d.vehicle_number, d.vehicle_model, d.rating as driver_rating, d.current_lat, d.current_lng FROM rides r JOIN ride_categories rc ON r.category_id=rc.id LEFT JOIN drivers d ON r.driver_id=d.id WHERE r.id=? AND r.user_id=?", [$rideId, $_SESSION['user_id']]);
if (!$ride) { header('Location: '.APP_URL.'/pages/dashboard.php'); exit; }

require_once __DIR__ . '/../includes/header.php';
?>
<nav class="navbar">
  <a href="<?= APP_URL ?>" class="navbar-brand"><div class="bolt"></div><span><span class="flash">Flash</span>Ride</span></a>
  <div class="nav-links">
    <a href="<?= APP_URL ?>/pages/dashboard.php" class="nav-btn outline">← Back</a>
  </div>
</nav>

<div style="padding:88px 1.5rem 2rem;max-width:1100px;margin:0 auto;">
  <div style="display:grid;grid-template-columns:380px 1fr;gap:1.5rem;">
    <!-- Status Card -->
    <div class="fade-in">
      <div class="ride-status-card mb-3">
        <div class="flex-between mb-2">
          <div>
            <h3>Live Tracking</h3>
            <p style="font-size:.82rem;color:var(--text-muted);"><?= htmlspecialchars($ride['ride_code']) ?></p>
          </div>
          <span class="badge badge-<?= getStatusBadge($ride['status']) ?>" id="rideBadge"><?= ucfirst($ride['status']) ?></span>
        </div>

        <!-- Timeline -->
        <div class="status-timeline">
          <?php
          $steps = [
            ['searching','Searching Driver','fas fa-search'],
            ['accepted','Driver Assigned','fas fa-user-check'],
            ['arrived','Driver Arrived','fas fa-map-marker-alt'],
            ['started','Ride Started','fas fa-play'],
            ['completed','Ride Completed','fas fa-check'],
          ];
          $order = ['searching'=>0,'accepted'=>1,'arrived'=>2,'started'=>3,'completed'=>4];
          $curOrder = $order[$ride['status']] ?? 0;
          foreach ($steps as $s): $sOrder = $order[$s[0]] ?? 0; ?>
          <div class="timeline-step <?= $sOrder < $curOrder ? 'done' : ($sOrder === $curOrder ? 'active' : '') ?>" id="step_<?= $s[0] ?>">
            <div class="step-dot">
              <?php if ($sOrder < $curOrder): ?><i class="fas fa-check"></i>
              <?php elseif ($sOrder === $curOrder): ?><i class="<?= $s[2] ?>"></i>
              <?php else: ?><i class="<?= $s[2] ?>"></i><?php endif; ?>
            </div>
            <span style="font-size:.88rem;"><?= $s[1] ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Route -->
        <div style="background:var(--dark-3);border-radius:var(--radius-sm);padding:.9rem;margin-top:1rem;">
          <div style="display:flex;gap:.8rem;margin-bottom:.6rem;">
            <span style="color:var(--success);font-size:1.1rem;">●</span>
            <span style="font-size:.83rem;"><?= htmlspecialchars(substr($ride['pickup_address'],0,50)) ?>...</span>
          </div>
          <div style="display:flex;gap:.8rem;">
            <span style="color:var(--primary);font-size:1.1rem;">●</span>
            <span style="font-size:.83rem;"><?= htmlspecialchars(substr($ride['dropoff_address'],0,50)) ?>...</span>
          </div>
        </div>
      </div>

      <!-- Driver Card -->
      <?php if ($ride['driver_id']): ?>
      <div class="card mb-3">
        <div class="driver-card" style="padding:0;background:transparent;">
          <div class="driver-avatar"><i class="fas fa-user"></i></div>
          <div class="driver-info">
            <div class="driver-name"><?= htmlspecialchars($ride['driver_name']) ?></div>
            <div class="driver-vehicle"><?= htmlspecialchars($ride['vehicle_model']??'') ?> · <?= htmlspecialchars($ride['vehicle_number']??'') ?></div>
            <div class="driver-rating">★ <?= number_format($ride['driver_rating'],1) ?></div>
          </div>
        </div>
        <div class="ride-actions">
          <a href="tel:<?= htmlspecialchars($ride['driver_phone']) ?>" class="contact-btn"><i class="fas fa-phone"></i> Call Driver</a>
          <button onclick="sendSOS()" class="sos-btn"><i class="fas fa-exclamation-triangle"></i> SOS</button>
        </div>
        <?php if (in_array($ride['status'], ['searching','accepted','arrived'])): ?>
        <button onclick="showCancelModal()" class="btn btn-ghost btn-block" style="margin-top:.6rem;">
          <i class="fas fa-times"></i> Cancel This Ride
        </button>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Fare -->
      <div class="card">
        <h4 class="mb-2">Fare Details</h4>
        <div class="fare-row"><span>Estimated Fare</span><span style="color:var(--primary);font-weight:700;"><?= formatCurrency($ride['estimated_fare']) ?></span></div>
        <div class="fare-row"><span>Payment</span><span><?= ucfirst($ride['payment_method']) ?></span></div>
        <?php if ($ride['status'] === 'completed'): ?>
        <div class="fare-row" style="font-weight:700;"><span>Final Fare</span><span style="color:var(--success);"><?= formatCurrency($ride['final_fare'] ?? $ride['estimated_fare']) ?></span></div>
        <?php endif; ?>
      </div>

      <!-- OTP Display -->
      <?php if (in_array($ride['status'], ['accepted','arrived','started']) && $ride['otp_for_start']): ?>
      <div class="card mt-3" style="text-align:center;border-color:var(--border-glow);">
        <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.05em;">
          <i class="fas fa-key" style="color:var(--primary)"></i> &nbsp;Your Ride OTP
        </p>
        <div style="font-size:3rem;font-weight:800;letter-spacing:.5em;color:var(--primary);font-family:var(--font-display);line-height:1.1;"><?= htmlspecialchars($ride['otp_for_start']) ?></div>
        <p style="font-size:.78rem;color:var(--text-muted);margin-top:.5rem;">
          <?php if ($ride['status'] === 'started'): ?>
            ✅ OTP verified — ride is in progress
          <?php else: ?>
            Show this to your driver to start the ride
          <?php endif; ?>
        </p>
      </div>
      <?php endif; ?>

      <?php if ($ride['status'] === 'completed'): ?>
      <div class="card mt-3" id="ratingCard">
        <?php if ($ride['driver_rating']): ?>
          <!-- Already rated -->
          <div style="text-align:center;padding:.5rem 0;">
            <div style="font-size:2rem;margin-bottom:.4rem;">✅</div>
            <h4>Thanks for your rating!</h4>
            <div style="font-size:1.8rem;color:var(--warning);letter-spacing:.1em;margin:.5rem 0;">
              <?= str_repeat('★', (int)$ride['driver_rating']) ?><?= str_repeat('☆', 5 - (int)$ride['driver_rating']) ?>
            </div>
            <?php if ($ride['user_review']): ?>
              <p style="font-size:.85rem;color:var(--text-muted);font-style:italic;">"<?= htmlspecialchars($ride['user_review']) ?>"</p>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <h4 style="margin-bottom:1rem;">Rate Your Ride</h4>
          <!-- Stars -->
          <div id="starRow" style="display:flex;gap:.4rem;justify-content:center;margin-bottom:1rem;">
            <?php for($i=1;$i<=5;$i++): ?>
            <span class="star-item" data-val="<?= $i ?>"
                  style="font-size:2.5rem;cursor:pointer;color:var(--dark-3);transition:color .15s,transform .15s;user-select:none;"
                  onmouseover="hoverStar(<?= $i ?>)"
                  onmouseout="unhoverStar()"
                  onclick="selectStar(<?= $i ?>)">★</span>
            <?php endfor; ?>
          </div>
          <p id="starLabel" style="text-align:center;font-size:.85rem;color:var(--text-muted);margin-bottom:1rem;min-height:20px;"></p>
          <textarea id="reviewText" class="form-control" placeholder="Write a review (optional)..."
                    rows="3" style="resize:none;margin-bottom:1rem;"></textarea>
          <button onclick="submitRating()" class="btn btn-primary btn-block btn-lg" id="submitRatingBtn">
            <i class="fas fa-star"></i> Submit Rating
          </button>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Map -->
    <div class="fade-in">
      <div id="trackingMap"></div>
      <div style="margin-top:.8rem;display:flex;gap:1rem;font-size:.8rem;">
        <span><span style="color:var(--success)">●</span> Pickup</span>
        <span><span style="color:var(--primary)">●</span> Drop</span>
        <span><span style="color:var(--info)">●</span> Driver</span>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const map = MapHelper.initMap('trackingMap', <?= $ride['pickup_lat'] ?>, <?= $ride['pickup_lng'] ?>, 14);
  MapHelper.addMarker(<?= $ride['pickup_lat'] ?>, <?= $ride['pickup_lng'] ?>, 'pickup', 'Pickup');
  MapHelper.addMarker(<?= $ride['dropoff_lat'] ?>, <?= $ride['dropoff_lng'] ?>, 'drop', 'Drop');
  MapHelper.drawRoute([[<?= $ride['pickup_lat'] ?>,<?= $ride['pickup_lng'] ?>],[<?= $ride['dropoff_lat'] ?>,<?= $ride['dropoff_lng'] ?>]]);

  <?php if ($ride['current_lat'] && $ride['current_lng']): ?>
  MapHelper.driverMarker = MapHelper.addMarker(<?= $ride['current_lat'] ?>, <?= $ride['current_lng'] ?>, 'driver', '<?= htmlspecialchars($ride['driver_name']) ?>');
  <?php endif; ?>

  // Poll for updates
  <?php if (!in_array($ride['status'],['completed','cancelled'])): ?>
  const pollInterval = setInterval(async () => {
    const res = await API.get('<?= APP_URL ?>/api/ride_status.php?id=<?= $rideId ?>');
    if (res.status === 'success') {
      const d = res.data;
      document.getElementById('rideBadge').textContent = d.status.charAt(0).toUpperCase() + d.status.slice(1);
      document.getElementById('rideBadge').className = `badge badge-${d.badge_class}`;
      if (d.driver_lat && d.driver_lng) {
        if (MapHelper.driverMarker) map.removeLayer(MapHelper.driverMarker);
        MapHelper.driverMarker = MapHelper.addMarker(d.driver_lat, d.driver_lng, 'driver', 'Driver');
      }
      if (d.status === 'completed' || d.status === 'cancelled') {
        clearInterval(pollInterval);
        if (d.status === 'completed') Toast.success('Ride Completed!', 'Please rate your experience');
        setTimeout(() => location.reload(), 2000);
      }
    }
  }, 5000);
  <?php endif; ?>
});

function sendSOS() {
  if (confirm('🚨 Send SOS alert to emergency contacts?')) {
    API.post('<?= APP_URL ?>/api/sos.php', { ride_id: <?= $rideId ?> });
    Toast.error('SOS Sent!', 'Emergency services notified!');
  }
}

// ── Star Rating ──────────────────────────────────────────────
var selectedRating = 0;
var starLabels = ['','😞 Poor','😐 Fair','🙂 Good','😊 Great','🤩 Excellent!'];

function hoverStar(val) {
  document.querySelectorAll('.star-item').forEach(function(s, i) {
    s.style.color    = i < val ? 'var(--warning)' : 'var(--dark-3)';
    s.style.transform = i < val ? 'scale(1.2)' : 'scale(1)';
  });
  var lbl = document.getElementById('starLabel');
  if (lbl) lbl.textContent = starLabels[val] || '';
}

function unhoverStar() {
  document.querySelectorAll('.star-item').forEach(function(s, i) {
    s.style.color    = i < selectedRating ? 'var(--warning)' : 'var(--dark-3)';
    s.style.transform = 'scale(1)';
  });
  var lbl = document.getElementById('starLabel');
  if (lbl) lbl.textContent = selectedRating ? starLabels[selectedRating] : '';
}

function selectStar(val) {
  selectedRating = val;
  document.querySelectorAll('.star-item').forEach(function(s, i) {
    s.style.color     = i < val ? 'var(--warning)' : 'var(--dark-3)';
    s.style.transform = i < val ? 'scale(1.1)'     : 'scale(1)';
  });
  var lbl = document.getElementById('starLabel');
  if (lbl) {
    lbl.textContent = starLabels[val] || '';
    lbl.style.color = 'var(--warning)';
    lbl.style.fontWeight = '700';
  }
}

async function submitRating() {
  if (selectedRating < 1) {
    Toast.warning('Select Stars', 'Please tap a star to rate your ride');
    // Shake the stars to draw attention
    var row = document.getElementById('starRow');
    if (row) {
      row.style.animation = 'none';
      row.style.transform = 'translateX(-6px)';
      setTimeout(function(){ row.style.transform = 'translateX(6px)'; }, 100);
      setTimeout(function(){ row.style.transform = 'translateX(-4px)'; }, 200);
      setTimeout(function(){ row.style.transform = 'translateX(0)'; }, 300);
    }
    return;
  }
  var review = document.getElementById('reviewText') ? document.getElementById('reviewText').value.trim() : '';
  var btn    = document.getElementById('submitRatingBtn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<div class="spinner" style="width:18px;height:18px;border-width:2px;margin:0 auto;"></div>'; }

  try {
    var res = await API.post('<?= APP_URL ?>/api/rate_ride.php', {
      ride_id: <?= $rideId ?>,
      rating:  selectedRating,
      review:  review
    });
    if (res.status === 'success') {
      Toast.success('Thank you! ⭐', 'Your rating has been submitted.');
      // Replace rating card with thank you message
      var card = document.getElementById('ratingCard');
      if (card) {
        card.innerHTML = '<div style="text-align:center;padding:1rem 0;">'
          + '<div style="font-size:2.5rem;margin-bottom:.5rem;">⭐</div>'
          + '<h4>Rating Submitted!</h4>'
          + '<div style="font-size:2rem;color:var(--warning);margin:.5rem 0;">'
          + '★'.repeat(selectedRating) + '<span style="color:var(--dark-3);">' + '★'.repeat(5 - selectedRating) + '</span>'
          + '</div>'
          + '<p style="color:var(--text-muted);font-size:.85rem;">Thank you for your feedback!</p>'
          + '</div>';
      }
    } else {
      Toast.error('Failed', res.message || 'Could not submit rating');
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-star"></i> Submit Rating'; }
    }
  } catch(e) {
    Toast.error('Error', 'Connection problem. Please try again.');
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-star"></i> Submit Rating'; }
  }
}
</script>

<!-- ══ CANCEL RIDE MODAL ══ -->
<div class="modal-overlay" id="cancelModal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-times-circle" style="color:var(--danger)"></i> Cancel Ride</h3>
      <button class="modal-close" onclick="Modal.close('cancelModal')">&times;</button>
    </div>
    <p style="color:var(--text-secondary);margin-bottom:1.2rem;font-size:.9rem;">Select a reason:</p>
    <div style="display:flex;flex-direction:column;gap:.6rem;margin-bottom:1.5rem;">
      <?php foreach (['Driver is taking too long','Wrong pickup location','Found another ride','Emergency / Change of plans','Driver asked to cancel','Other reason'] as $cr): ?>
      <label style="display:flex;align-items:center;gap:.8rem;padding:.75rem 1rem;background:var(--dark-3);border-radius:var(--radius-sm);cursor:pointer;border:1.5px solid transparent;transition:border-color .2s;" onclick="this.style.borderColor='var(--primary)'">
        <input type="radio" name="cancelReason" value="<?= htmlspecialchars($cr) ?>" style="accent-color:var(--primary);">
        <span style="font-size:.88rem;"><?= htmlspecialchars($cr) ?></span>
      </label>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;gap:.8rem;">
      <button onclick="Modal.close('cancelModal')" class="btn btn-ghost" style="flex:1">Keep Ride</button>
      <button onclick="doCancel()" class="btn btn-danger" style="flex:1" id="doCancelBtn">
        <i class="fas fa-times"></i> Yes, Cancel
      </button>
    </div>
  </div>
</div>

<script>
function showCancelModal() { Modal.open('cancelModal'); }
async function doCancel() {
  var sel = document.querySelector('input[name="cancelReason"]:checked');
  if (!sel) { Toast.warning('Select reason','Please pick a reason'); return; }
  var btn = document.getElementById('doCancelBtn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner" style="width:18px;height:18px;border-width:2px;margin:0 auto;"></div>';
  try {
    var res = await API.post('<?= APP_URL ?>/api/cancel_ride.php', { ride_id: <?= $rideId ?>, reason: sel.value });
    Modal.close('cancelModal');
    if (res.status === 'success') {
      Toast.success('Cancelled','Ride cancelled successfully');
      setTimeout(function(){ window.location.href='<?= APP_URL ?>/pages/dashboard.php'; }, 1200);
    } else {
      Toast.error('Error', res.message);
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-times"></i> Yes, Cancel';
    }
  } catch(e) {
    Toast.error('Error','Try again.');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-times"></i> Yes, Cancel';
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
