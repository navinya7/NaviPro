<?php
$pageTitle = 'Book a Ride';
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireUserLogin();

$categories = [];
$user       = ['wallet_balance' => 0, 'full_name' => ''];
$surgeNow   = getSurgeMultiplier();
try {
    $db         = Database::getInstance();
    $categories = $db->fetchAll("SELECT * FROM ride_categories WHERE is_active = 1 ORDER BY id");
    $user       = $db->fetch("SELECT * FROM users WHERE id = ?", [(int)$_SESSION['user_id']]) ?? ['wallet_balance'=>0,'full_name'=>'Rider'];
} catch (Exception $e) {
    // DB not ready — show setup instructions
    $dbError = $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>
<nav class="navbar">
  <a href="<?= APP_URL ?>" class="navbar-brand"><div class="bolt"></div><span><span class="flash">Flash</span>Ride</span></a>
  <div class="nav-links">
    <a href="<?= APP_URL ?>/pages/dashboard.php">Dashboard</a>
    <a href="<?= APP_URL ?>/pages/ride_history.php">History</a>
    <a href="<?= APP_URL ?>/pages/wallet.php">
      Wallet <span style="color:var(--success)">₹<?= number_format((float)$user['wallet_balance'],0) ?></span>
    </a>
    <a href="<?= APP_URL ?>/pages/logout.php" class="nav-btn outline">Logout</a>
  </div>
</nav>

<div id="toast-container"></div>

<?php if (isset($dbError)): ?>
<div style="max-width:1100px;margin:88px auto 0;padding:0 1.5rem 0;">
  <div class="alert alert-danger">
    <i class="fas fa-database"></i>
    <div>
      <strong>Database tables missing!</strong>
      Run the installer to create them:
      <a href="<?= APP_URL ?>/install.php" style="color:#fff;font-weight:700;text-decoration:underline;margin-left:.4rem;">
        Open Installer →
      </a>
      <br><small style="opacity:.75;font-size:.78rem;"><?= htmlspecialchars($dbError ?? '') ?></small>
    </div>
  </div>
</div>
<?php endif; ?>


<?php if (isset($dbError)): ?>
<div style="margin:88px 1.5rem 0;max-width:1100px;margin-left:auto;margin-right:auto;">
  <div class="alert alert-danger" style="margin-top:88px;">
    <i class="fas fa-database"></i>
    <div>
      <strong>Database tables not found!</strong><br>
      Please import <code>database.sql</code> first.<br><br>
      <strong>Quick fix:</strong>
      <ol style="margin:.5rem 0 0 1.2rem;line-height:2;">
        <li>Visit <a href="<?= APP_URL ?>/install.php" style="color:var(--warning)">install.php</a> for one-click setup</li>
        <li><em>OR</em> open phpMyAdmin → create database <code>flashride_db</code> → Import → select <code>database.sql</code></li>
      </ol>
    </div>
  </div>
</div>
<?php endif; ?>

<div style="padding:88px 1.5rem 2rem; max-width:1100px; margin:0 auto;">
  <div class="page-header">
    <h2><i class="fas fa-bolt" style="color:var(--primary)"></i> Book a Ride</h2>
    <p>Enter your destination and choose your ride type</p>
  </div>

  <div class="booking-grid">

    <!-- ══ LEFT PANEL ══════════════════════════════════════ -->
    <div class="booking-panel fade-in">

      <!-- STEP 1: booking form -->
      <div id="bookingStep1">
        <h4 style="margin-bottom:1.2rem;">Where are you going?</h4>

        <!-- Pickup -->
        <div class="form-group" style="position:relative;">
          <label class="form-label">
            <span style="color:var(--success)">●</span> Pickup Location
          </label>
          <div class="input-group">
            <i class="fas fa-map-marker-alt input-icon" style="color:var(--success)"></i>
            <input type="text" id="pickupInput" class="form-control"
                   placeholder="Your current location" autocomplete="off">
            <button onclick="useCurrentLocation()" title="Use GPS"
                    style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--primary);cursor:pointer;font-size:1rem;">
              <i class="fas fa-crosshairs"></i>
            </button>
          </div>
          <div id="pickupResults" class="suggest-dropdown" style="display:none;"></div>
        </div>

        <!-- Drop -->
        <div class="form-group" style="position:relative;">
          <label class="form-label">
            <span style="color:var(--primary)">●</span> Drop Location
          </label>
          <div class="input-group">
            <i class="fas fa-map-pin input-icon" style="color:var(--primary)"></i>
            <input type="text" id="dropInput" class="form-control"
                   placeholder="Where to?" autocomplete="off">
          </div>
          <div id="dropResults" class="suggest-dropdown" style="display:none;"></div>
        </div>

        <!-- Route summary -->
        <div id="routeInfo" style="display:none;background:var(--dark-3);border-radius:var(--radius-sm);padding:.9rem;margin-bottom:1rem;">
          <div style="display:flex;justify-content:space-between;font-size:.85rem;">
            <span><i class="fas fa-route" style="color:var(--primary)"></i> &nbsp;<strong id="routeDist">--</strong></span>
            <span><i class="fas fa-clock" style="color:var(--info)"></i> &nbsp;<strong id="routeEta">--</strong></span>
          </div>
        </div>

        <!-- Vehicle types -->
        <h4 style="margin-bottom:.8rem;">Choose Ride</h4>
        <div class="ride-types" id="rideTypesGrid">
          <?php
          $icons = ['bike'=>'🏍️','auto'=>'🛺','cab'=>'🚗'];
          foreach ($categories as $cat):
            $icon = $icons[$cat['type']] ?? '🚗';
          ?>
          <div class="ride-type-card"
               data-id="<?= $cat['id'] ?>"
               data-base="<?= $cat['base_fare'] ?>"
               data-perkm="<?= $cat['per_km_rate'] ?>"
               data-permin="<?= $cat['per_min_rate'] ?>"
               data-min="<?= $cat['min_fare'] ?>"
               data-type="<?= htmlspecialchars($cat['type']) ?>">
            <div class="icon"><?= $icon ?></div>
            <div class="name"><?= htmlspecialchars($cat['name']) ?></div>
            <div class="price" id="price_<?= $cat['id'] ?>">₹<?= number_format($cat['base_fare'],0) ?>+</div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Promo -->
        <div class="form-group" style="display:flex;gap:.5rem;margin-top:.5rem;">
          <input type="text" id="promoInput" class="form-control"
                 placeholder="Promo code (e.g. FLASH50)" style="text-transform:uppercase;">
          <button onclick="applyPromo()" class="btn btn-ghost btn-sm">Apply</button>
        </div>
        <div id="promoMsg" style="font-size:.82rem;margin-top:-.4rem;margin-bottom:.5rem;min-height:18px;"></div>

        <!-- Payment -->
        <div class="form-group">
          <label class="form-label">Payment Method</label>
          <select id="paymentMethod" class="form-control">
            <option value="cash">💵 Cash</option>
            <option value="wallet">💰 Wallet (₹<?= number_format((float)$user['wallet_balance'],2) ?>)</option>
            <option value="upi">📱 UPI</option>
          </select>
        </div>

        <!-- Fare breakdown -->
        <div id="fareBreakdown" style="display:none;" class="fare-breakdown">
          <div class="fare-row"><span>Base Fare</span><span id="fbBase">—</span></div>
          <div class="fare-row"><span>Distance Charge</span><span id="fbDist">—</span></div>
          <div class="fare-row"><span>Time Charge</span><span id="fbTime">—</span></div>
          <div id="surgeBadge" style="display:none;" class="fare-row">
            <span style="color:var(--warning)">⚡ Surge (<?= $surgeNow ?>x)</span>
            <span style="color:var(--warning)">Applied</span>
          </div>
          <div id="promoRow" style="display:none;" class="fare-row">
            <span style="color:var(--success)">🎁 Promo Discount</span>
            <span id="fbPromo" style="color:var(--success)">—</span>
          </div>
          <div class="fare-row total">
            <span>Estimated Total</span>
            <span id="fbTotal" style="color:var(--primary)">—</span>
          </div>
        </div>

        <button class="btn btn-primary btn-block btn-lg" style="margin-top:1rem;"
                id="bookBtn" disabled onclick="bookRide()">
          <i class="fas fa-bolt"></i> Book Now
        </button>
      </div>

      <!-- STEP 2: searching -->
      <div id="bookingStep2" style="display:none;text-align:center;padding:1.5rem 0;">
        <div class="spinner" style="width:64px;height:64px;border-width:5px;margin:0 auto 1.5rem;"></div>
        <h3>Finding Your Driver</h3>
        <p style="color:var(--text-muted);margin:.5rem 0 1rem;">Searching nearby drivers...</p>
        <p style="font-size:.85rem;color:var(--text-muted);">
          Estimated wait: <span id="waitTimer" style="color:var(--primary);font-weight:800;font-size:1rem;">2:00</span>
        </p>
        <!-- Show OTP early so rider doesn't miss it -->
        <div id="otpEarlyBox" style="margin-top:1.2rem;display:none;background:var(--dark-3);border:1px solid var(--border-glow);border-radius:var(--radius);padding:.9rem;text-align:center;">
          <p style="font-size:.75rem;color:var(--text-muted);margin-bottom:.3rem;">Your Ride OTP — show to driver</p>
          <div id="rideOtpEarly" style="font-size:2.2rem;font-weight:800;letter-spacing:.5em;color:var(--primary);font-family:var(--font-display);">----</div>
        </div>
        <button onclick="cancelSearch()" class="btn btn-ghost btn-sm" style="margin-top:1rem;">
          <i class="fas fa-times"></i> Cancel Search
        </button>
      </div>

      <!-- STEP 3: driver assigned -->
      <div id="bookingStep3" style="display:none;">
        <div style="text-align:center;margin-bottom:1.5rem;">
          <div style="font-size:3rem;margin-bottom:.4rem;">🎉</div>
          <h3>Driver On The Way!</h3>
          <p style="color:var(--text-muted);font-size:.88rem;" id="etaText">Arriving in a moment...</p>
        </div>

        <!-- Driver card -->
        <div class="driver-card" style="background:var(--dark-3);border-radius:var(--radius);padding:1rem;margin-bottom:1rem;">
          <div class="driver-avatar"><i class="fas fa-motorcycle"></i></div>
          <div class="driver-info">
            <div class="driver-name" id="driverName">—</div>
            <div class="driver-vehicle" id="driverVehicle">—</div>
            <div class="driver-rating" id="driverRating">⭐ —</div>
          </div>
          <!-- Live ETA badge -->
          <div style="text-align:center;">
            <div id="etaBadge" style="font-family:var(--font-display);font-size:1.5rem;font-weight:800;color:var(--info);">—</div>
            <div style="font-size:.7rem;color:var(--text-muted);">ETA</div>
          </div>
        </div>

        <!-- OTP -->
        <div style="background:linear-gradient(135deg,var(--dark-2),var(--dark-3));border:1px solid var(--border-glow);border-radius:var(--radius);padding:1rem;text-align:center;margin-bottom:1rem;">
          <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:.4rem;">Show this OTP to your driver to start</p>
          <div id="rideOtp" style="font-size:2.5rem;font-weight:800;letter-spacing:.5em;color:var(--primary);font-family:var(--font-display);">
            ----
          </div>
        </div>

        <!-- Progress bar showing driver approaching -->
        <div style="margin-bottom:1rem;">
          <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--text-muted);margin-bottom:.4rem;">
            <span>Driver location</span><span>Your pickup</span>
          </div>
          <div style="background:var(--dark-3);border-radius:50px;height:6px;overflow:hidden;">
            <div id="approachBar" style="height:100%;border-radius:50px;background:linear-gradient(90deg,var(--info),var(--primary));width:5%;transition:width 1s ease;"></div>
          </div>
        </div>

        <div style="display:flex;gap:.7rem;">
          <a href="#" id="trackRideBtn" class="btn btn-primary" style="flex:1;">
            <i class="fas fa-map-marked-alt"></i> Track Live
          </a>
          <button onclick="sendSOS()" class="sos-btn">
            <i class="fas fa-exclamation-triangle"></i> SOS
          </button>
        </div>
      </div>

    </div><!-- /booking-panel -->

    <!-- ══ RIGHT: MAP ════════════════════════════════════════ -->
    <div class="fade-in" style="position:sticky;top:88px;">
      <div id="rideMap"></div>

      <!-- Map legend -->
      <div style="margin-top:.8rem;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:.8rem 1rem;display:flex;gap:1.2rem;font-size:.8rem;flex-wrap:wrap;">
        <span><span style="color:var(--success)">●</span> Pickup</span>
        <span><span style="color:var(--primary)">●</span> Drop</span>
        <span id="driverLegend" style="display:none;"><span style="color:var(--info)">🏍️</span> Your Driver</span>
        <span style="margin-left:auto;color:var(--text-muted);">Click map to set points</span>
      </div>
    </div>

  </div><!-- /booking-grid -->
</div>

<!-- ══ ALL JAVASCRIPT ══════════════════════════════════════════ -->
<script>
// ── State ──────────────────────────────────────────────────────
var pickupCoords    = null;
var dropCoords      = null;
var selectedCat     = null;
var promoDiscount   = 0;
var promoId         = null;
var currentRideId   = null;
var searchTimer     = null;
var pollTimer       = null;
var driverAnimTimer = null;
var surge           = <?= $surgeNow ?>;

// ── Driver animation state ─────────────────────────────────────
var driverPos       = null;   // { lat, lng }  current animated position
var driverTarget    = null;   // { lat, lng }  pickup point
var driverMarkerEl  = null;   // Leaflet marker

// ══════════════════════════════════════════════════════════════
// MAP INIT
// ══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {

  var map = MapHelper.initMap('rideMap', 18.5204, 73.8567, 13);

  // Auto-detect current location for pickup
  MapHelper.getUserLocation(function(loc) {
    map.setView([loc.lat, loc.lng], 15);
    pickupCoords = loc;
    MapHelper.reverseGeocode(loc.lat, loc.lng, function(addr) {
      document.getElementById('pickupInput').value = addr;
    });
    if (MapHelper.pickupMarker) map.removeLayer(MapHelper.pickupMarker);
    MapHelper.pickupMarker = addDot(map, loc.lat, loc.lng, 'var(--success)', 'Your Pickup');
  });

  // Click map to place markers
  map.on('click', function(e) {
    var lat = e.latlng.lat, lng = e.latlng.lng;
    if (!pickupCoords) {
      pickupCoords = {lat:lat, lng:lng};
      if (MapHelper.pickupMarker) map.removeLayer(MapHelper.pickupMarker);
      MapHelper.pickupMarker = addDot(map, lat, lng, 'var(--success)', 'Pickup');
      MapHelper.reverseGeocode(lat, lng, function(a){ document.getElementById('pickupInput').value = a; });
    } else {
      dropCoords = {lat:lat, lng:lng};
      if (MapHelper.dropMarker) map.removeLayer(MapHelper.dropMarker);
      MapHelper.dropMarker = addDot(map, lat, lng, 'var(--primary)', 'Drop');
      MapHelper.reverseGeocode(lat, lng, function(a){ document.getElementById('dropInput').value = a; });
      updateRoute();
    }
  });

  // Address autocomplete
  initAddressSuggest('pickupInput', 'pickupResults', function(loc) {
    pickupCoords = {lat: loc.lat, lng: loc.lng};
    if (MapHelper.pickupMarker) map.removeLayer(MapHelper.pickupMarker);
    MapHelper.pickupMarker = addDot(map, loc.lat, loc.lng, 'var(--success)', 'Pickup');
    map.setView([loc.lat, loc.lng], 15);
    if (dropCoords) updateRoute();
  });
  initAddressSuggest('dropInput', 'dropResults', function(loc) {
    dropCoords = {lat: loc.lat, lng: loc.lng};
    if (MapHelper.dropMarker) map.removeLayer(MapHelper.dropMarker);
    MapHelper.dropMarker = addDot(map, loc.lat, loc.lng, 'var(--primary)', 'Drop');
    if (pickupCoords) updateRoute();
  });

  // Ride type selection
  document.querySelectorAll('.ride-type-card').forEach(function(card) {
    card.addEventListener('click', function() {
      document.querySelectorAll('.ride-type-card').forEach(function(c){ c.classList.remove('active'); });
      card.classList.add('active');
      selectedCat = {
        id:     card.dataset.id,
        base:   parseFloat(card.dataset.base),
        perkm:  parseFloat(card.dataset.perkm),
        permin: parseFloat(card.dataset.permin),
        min:    parseFloat(card.dataset.min),
        type:   card.dataset.type
      };
      updateFareDisplay();
    });
  });
});

// ── Dot marker helper (cleaner than default Leaflet pins) ───────
function addDot(map, lat, lng, color, label) {
  var icon = L.divIcon({
    className: '',
    html: '<div style="width:16px;height:16px;background:' + color + ';border-radius:50%;border:3px solid rgba(255,255,255,.9);box-shadow:0 2px 10px rgba(0,0,0,.5);"></div>',
    iconSize: [16,16], iconAnchor: [8,8]
  });
  return L.marker([lat, lng], {icon: icon}).addTo(map).bindPopup(label);
}

// ── Animated driver dot ─────────────────────────────────────────
function makeDriverIcon(vehicleType) {
  var emoji = {'bike':'🏍️','auto':'🛺','cab':'🚗'}[vehicleType] || '🚗';
  return L.divIcon({
    className: '',
    html: '<div style="font-size:22px;filter:drop-shadow(0 2px 4px rgba(0,0,0,.6));animation:driverBob .8s ease-in-out infinite alternate;">' + emoji + '</div>',
    iconSize: [28,28], iconAnchor: [14,14]
  });
}

// ── GPS location ────────────────────────────────────────────────
function useCurrentLocation() {
  MapHelper.getUserLocation(function(loc) {
    pickupCoords = loc;
    MapHelper.reverseGeocode(loc.lat, loc.lng, function(addr){
      document.getElementById('pickupInput').value = addr;
    });
    if (MapHelper.pickupMarker) MapHelper.map.removeLayer(MapHelper.pickupMarker);
    MapHelper.pickupMarker = addDot(MapHelper.map, loc.lat, loc.lng, 'var(--success)', 'Your Pickup');
    MapHelper.map.setView([loc.lat, loc.lng], 15);
    Toast.info('Location set', 'Using your current GPS location');
  });
}

// ══════════════════════════════════════════════════════════════
// ROUTE & FARE
// ══════════════════════════════════════════════════════════════
function updateRoute() {
  if (!pickupCoords || !dropCoords) return;
  var dist = haversineKm(pickupCoords, dropCoords);
  var eta  = Math.round(dist * 3 + 4);
  document.getElementById('routeInfo').style.display = 'block';
  document.getElementById('routeDist').textContent = dist.toFixed(1) + ' km';
  document.getElementById('routeEta').textContent  = eta + ' min';
  MapHelper.drawRoute([[pickupCoords.lat, pickupCoords.lng],[dropCoords.lat, dropCoords.lng]]);
  updateFareDisplay();
  // Update per-card prices
  document.querySelectorAll('.ride-type-card').forEach(function(card) {
    var b = parseFloat(card.dataset.base), k = parseFloat(card.dataset.perkm),
        m = parseFloat(card.dataset.permin), mn = parseFloat(card.dataset.min);
    var f = Math.max((b + dist*k + eta*m)*surge, mn);
    card.querySelector('.price').textContent = '₹' + Math.round(f);
  });
}

function updateFareDisplay() {
  if (!pickupCoords || !dropCoords || !selectedCat) return;
  var dist = haversineKm(pickupCoords, dropCoords);
  var mins = Math.round(dist * 3 + 4);
  var base = selectedCat.base;
  var dk   = dist * selectedCat.perkm;
  var tm   = mins * selectedCat.permin;
  var raw  = (base + dk + tm) * surge;
  var fare = Math.max(raw, selectedCat.min);
  var final= Math.max(fare - promoDiscount, 0);
  document.getElementById('fareBreakdown').style.display = 'block';
  document.getElementById('fbBase').textContent  = fmtCurrency(base);
  document.getElementById('fbDist').textContent  = fmtCurrency(dk);
  document.getElementById('fbTime').textContent  = fmtCurrency(tm);
  document.getElementById('fbTotal').textContent = fmtCurrency(final);
  document.getElementById('surgeBadge').style.display = surge > 1 ? 'flex' : 'none';
  document.getElementById('promoRow').style.display   = promoDiscount > 0 ? 'flex' : 'none';
  document.getElementById('fbPromo').textContent      = '-' + fmtCurrency(promoDiscount);
  document.getElementById('bookBtn').disabled = false;
}

function haversineKm(a, b) {
  var R = 6371;
  var dLat = (b.lat - a.lat) * Math.PI / 180;
  var dLng = (b.lng - a.lng) * Math.PI / 180;
  var aa   = Math.sin(dLat/2)*Math.sin(dLat/2)
           + Math.cos(a.lat*Math.PI/180)*Math.cos(b.lat*Math.PI/180)
           * Math.sin(dLng/2)*Math.sin(dLng/2);
  return R * 2 * Math.atan2(Math.sqrt(aa), Math.sqrt(1-aa));
}

// ── Promo ────────────────────────────────────────────────────────
async function applyPromo() {
  var code = document.getElementById('promoInput').value.trim().toUpperCase();
  if (!code) return;
  if (!pickupCoords || !dropCoords || !selectedCat) {
    Toast.warning('Select route first', 'Set pickup, drop and vehicle type first');
    return;
  }
  var dist  = haversineKm(pickupCoords, dropCoords);
  var mins  = Math.round(dist * 3 + 4);
  var fare  = Math.max((selectedCat.base + dist*selectedCat.perkm + mins*selectedCat.permin)*surge, selectedCat.min);
  var res   = await API.post('<?= APP_URL ?>/api/apply_promo.php', {code: code, fare: fare});
  var msg   = document.getElementById('promoMsg');
  if (res.status === 'success') {
    promoDiscount = res.data.discount;
    promoId       = res.data.promo_id;
    msg.style.color = 'var(--success)';
    msg.textContent = '✅ Promo applied! You save ' + fmtCurrency(promoDiscount);
    Toast.success('Promo Applied!', 'You save ' + fmtCurrency(promoDiscount));
  } else {
    promoDiscount = 0; promoId = null;
    msg.style.color = 'var(--danger)';
    msg.textContent = '❌ ' + res.message;
  }
  updateFareDisplay();
}

// ══════════════════════════════════════════════════════════════
// BOOKING
// ══════════════════════════════════════════════════════════════
async function bookRide() {
  if (!pickupCoords || !dropCoords || !selectedCat) {
    Toast.warning('Incomplete', 'Please fill in pickup, drop and vehicle type');
    return;
  }
  var btn = document.getElementById('bookBtn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner" style="width:20px;height:20px;border-width:2px;margin:0 auto;"></div>';

  var dist = haversineKm(pickupCoords, dropCoords);
  var payload = {
    category_id:      selectedCat.id,
    pickup_address:   document.getElementById('pickupInput').value,
    pickup_lat:       pickupCoords.lat,
    pickup_lng:       pickupCoords.lng,
    dropoff_address:  document.getElementById('dropInput').value,
    dropoff_lat:      dropCoords.lat,
    dropoff_lng:      dropCoords.lng,
    distance_km:      parseFloat(dist.toFixed(2)),
    payment_method:   document.getElementById('paymentMethod').value,
    promo_id:         promoId
  };

  try {
    var res = await API.post('<?= APP_URL ?>/api/book_ride.php', payload);
    if (res.status === 'success') {
      currentRideId  = res.data.ride_id;
      // Store OTP immediately from booking response — show in both steps
      if (res.data.otp) {
        document.getElementById('rideOtp').textContent      = res.data.otp;
        document.getElementById('rideOtpEarly').textContent = res.data.otp;
        document.getElementById('otpEarlyBox').style.display = 'block';
      }
      showStep(2);
      startCountdown(120, 'waitTimer', function(){ cancelSearch(); });
      pollForDriver();
      Toast.info('Searching...', 'Looking for a driver near you');
    } else {
      Toast.error('Booking Failed', res.message);
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-bolt"></i> Book Now';
    }
  } catch(e) {
    Toast.error('Error', 'Connection problem. Please try again.');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-bolt"></i> Book Now';
  }
}

// ══════════════════════════════════════════════════════════════
// POLL FOR DRIVER ASSIGNMENT
// ══════════════════════════════════════════════════════════════
function pollForDriver() {
  if (pollTimer) clearTimeout(pollTimer);
  pollTimer = setTimeout(async function() {
    if (!currentRideId) return;
    try {
      var res = await API.get('<?= APP_URL ?>/api/ride_status.php?id=' + currentRideId);
      if (res.status === 'success') {
        var d = res.data;
        if (d.status === 'accepted' || d.status === 'arrived' || d.status === 'started') {
          onDriverAssigned(d);
        } else if (d.status === 'cancelled') {
          showStep(1);
          Toast.error('Cancelled', 'No driver found. Please try again.');
        } else {
          pollForDriver(); // keep polling
        }
      } else {
        pollForDriver();
      }
    } catch(e) {
      pollForDriver();
    }
  }, 3000); // check every 3 seconds
}

// ══════════════════════════════════════════════════════════════
// DRIVER ASSIGNED — START ANIMATION
// ══════════════════════════════════════════════════════════════
function onDriverAssigned(data) {
  // Populate step 3 UI
  document.getElementById('driverName').textContent    = data.driver_name    || 'Your Driver';
  document.getElementById('driverVehicle').textContent = (data.vehicle_model || '') + '  ·  ' + (data.vehicle_number || '');
  document.getElementById('driverRating').textContent  = '⭐ ' + (parseFloat(data.driver_rating || 4.8).toFixed(1));
  document.getElementById('rideOtp').textContent       = data.otp_for_start || data.otp || document.getElementById('rideOtp').textContent || '----';
  document.getElementById('trackRideBtn').href         = '<?= APP_URL ?>/pages/track_ride.php?id=' + currentRideId;
  document.getElementById('driverLegend').style.display = '';
  showStep(3);
  Toast.success('Driver Found! 🎉', data.driver_name + ' is on the way to you!');

  // Determine driver starting position
  // Use actual driver coords if available, otherwise place them ~800m away from pickup
  var startLat, startLng;
  if (data.driver_lat && data.driver_lng) {
    startLat = parseFloat(data.driver_lat);
    startLng = parseFloat(data.driver_lng);
  } else {
    // Simulate: place driver ~600-900m away in a random direction
    var angle  = Math.random() * 2 * Math.PI;
    var distKm = 0.15 + Math.random() * 0.25; // 150-400m (close enough to arrive in ~1 min)
    startLat = pickupCoords.lat + (distKm / 111) * Math.cos(angle);
    startLng = pickupCoords.lng + (distKm / 111) * Math.sin(angle) / Math.cos(pickupCoords.lat * Math.PI/180);
  }

  driverPos    = {lat: startLat, lng: startLng};
  driverTarget = {lat: pickupCoords.lat, lng: pickupCoords.lng};

  // Place driver on map
  if (driverMarkerEl) MapHelper.map.removeLayer(driverMarkerEl);
  var vType   = data.vehicle_type || (selectedCat ? selectedCat.type : 'bike');
  driverMarkerEl = L.marker([driverPos.lat, driverPos.lng], {
    icon: makeDriverIcon(vType),
    zIndexOffset: 1000
  }).addTo(MapHelper.map);
  driverMarkerEl.bindPopup(data.driver_name + ' — On the way!').openPopup();

  // Zoom out to show both driver and pickup
  MapHelper.map.fitBounds([
    [driverPos.lat, driverPos.lng],
    [pickupCoords.lat, pickupCoords.lng],
    [dropCoords.lat, dropCoords.lng]
  ], {padding: [50, 50]});

  // Calculate ETA
  var distToPickup = haversineKm(driverPos, driverTarget);
  updateETA(1); // always show ~1 min ETA

  // Start smooth animation — always reaches pickup within 55 seconds
  animateDriverToPickup(distToPickup, 1, vType);

  // Keep polling for status updates (arrived, started etc.)
  keepPollingStatus();
}

// ══════════════════════════════════════════════════════════════
// SMOOTH DRIVER ANIMATION
// ══════════════════════════════════════════════════════════════
function animateDriverToPickup(totalDistKm, etaMins, vType) {
  if (driverAnimTimer) clearInterval(driverAnimTimer);

  // ── Always arrive within 55 seconds, update every 300ms ──────
  var ARRIVE_IN_MS = 55000;   // driver reaches pickup in 55s
  var STEP_MS      = 300;     // smooth: update position every 300ms
  var totalSteps   = ARRIVE_IN_MS / STEP_MS;  // = ~183 steps
  var stepsDone    = 0;
  var secsLeft     = 60;      // countdown display starts at 60

  // Pre-compute a natural curved path (slight S-curve, not straight line)
  // Using a bezier-like midpoint offset so it looks like it follows a road
  var midLat = (driverPos.lat + driverTarget.lat) / 2 + (Math.random() - 0.5) * 0.003;
  var midLng = (driverPos.lng + driverTarget.lng) / 2 + (Math.random() - 0.5) * 0.003;

  // Start ETA countdown display separately (every 1 second)
  document.getElementById('etaBadge').textContent = '~1 min';
  document.getElementById('etaText').textContent  = 'Driver is on the way!';
  var countdownTimer = setInterval(function() {
    secsLeft--;
    if (secsLeft > 0) {
      document.getElementById('etaBadge').textContent = secsLeft + 's';
    }
  }, 1000);

  driverAnimTimer = setInterval(function() {
    stepsDone++;
    var progress = Math.min(stepsDone / totalSteps, 1.0);

    // Quadratic bezier interpolation through midpoint (curved path)
    var t   = progress;
    var mt  = 1 - t;
    var newLat = mt*mt*driverPos.lat + 2*mt*t*midLat + t*t*driverTarget.lat;
    var newLng = mt*mt*driverPos.lng + 2*mt*t*midLng + t*t*driverTarget.lng;

    // Tiny micro-jitter for realistic road feel (only in middle 20-80%)
    if (progress > 0.2 && progress < 0.8) {
      newLat += (Math.random() - 0.5) * 0.00004;
      newLng += (Math.random() - 0.5) * 0.00004;
    }

    // Snap cleanly to pickup in last 3%
    if (progress > 0.97) {
      newLat = driverTarget.lat;
      newLng = driverTarget.lng;
    }

    if (driverMarkerEl) driverMarkerEl.setLatLng([newLat, newLng]);

    // Approach bar: 5% → 100%
    var pct = Math.round(5 + progress * 95);
    document.getElementById('approachBar').style.width = pct + '%';

    // ARRIVED
    if (progress >= 1.0) {
      clearInterval(driverAnimTimer);
      clearInterval(countdownTimer);
      document.getElementById('approachBar').style.width   = '100%';
      document.getElementById('approachBar').style.background = 'var(--success)';
      document.getElementById('etaBadge').textContent      = 'HERE ✓';
      document.getElementById('etaBadge').style.color      = 'var(--success)';
      document.getElementById('etaText').textContent       = 'Driver has arrived! Show your OTP.';
      if (driverMarkerEl) {
        driverMarkerEl.setLatLng([driverTarget.lat, driverTarget.lng]);
        driverMarkerEl.setPopupContent('🎯 Arrived at your pickup!').openPopup();
      }
      Toast.success('Driver Arrived! 📍', 'Show your OTP to start the ride.');
    }
  }, STEP_MS);
}

function easeInOut(t) {
  return t < 0.5 ? 2*t*t : -1+(4-2*t)*t;
}

function updateETA(mins) {
  var badge = document.getElementById('etaBadge');
  var text  = document.getElementById('etaText');
  if (mins <= 0) {
    badge.textContent = 'HERE';
    if (text) text.textContent = 'Driver has arrived!';
  } else if (mins === 1) {
    badge.textContent = '< 1 min';
    if (text) text.textContent = 'Driver is very close!';
  } else {
    badge.textContent = mins + ' min';
    if (text) text.textContent = 'Driver is on the way to you';
  }
}

// ══════════════════════════════════════════════════════════════
// KEEP POLLING STATUS AFTER ASSIGNMENT
// ══════════════════════════════════════════════════════════════
function keepPollingStatus() {
  var statusPoll = setInterval(async function() {
    if (!currentRideId) { clearInterval(statusPoll); return; }
    try {
      var res = await API.get('<?= APP_URL ?>/api/ride_status.php?id=' + currentRideId);
      if (res.status === 'success') {
        var d = res.data;
        // Update driver position if it changed
        if (d.driver_lat && d.driver_lng && driverMarkerEl) {
          var newLat = parseFloat(d.driver_lat);
          var newLng = parseFloat(d.driver_lng);
          if (Math.abs(newLat - driverPos.lat) > 0.0001 || Math.abs(newLng - driverPos.lng) > 0.0001) {
            driverPos = {lat: newLat, lng: newLng};
          }
        }
        if (d.status === 'completed' || d.status === 'cancelled') {
          clearInterval(statusPoll);
          if (d.status === 'completed') {
            Toast.success('Ride Completed!', 'Please rate your experience');
            setTimeout(function(){ window.location.href = '<?= APP_URL ?>/pages/track_ride.php?id=' + currentRideId; }, 1500);
          }
        }
      }
    } catch(e) {}
  }, 5000);
}

// ══════════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════════
function showStep(n) {
  document.getElementById('bookingStep1').style.display = n === 1 ? '' : 'none';
  document.getElementById('bookingStep2').style.display = n === 2 ? '' : 'none';
  document.getElementById('bookingStep3').style.display = n === 3 ? '' : 'none';
}

function cancelSearch() {
  if (pollTimer) clearTimeout(pollTimer);
  if (currentRideId) {
    API.post('<?= APP_URL ?>/api/cancel_ride.php', {ride_id: currentRideId, reason: 'User cancelled'});
  }
  currentRideId = null;
  showStep(1);
  document.getElementById('bookBtn').disabled = false;
  document.getElementById('bookBtn').innerHTML = '<i class="fas fa-bolt"></i> Book Now';
  Toast.info('Cancelled', 'Ride search cancelled');
}

function sendSOS() {
  if (!currentRideId) return;
  if (confirm('🚨 Send SOS emergency alert?')) {
    API.post('<?= APP_URL ?>/api/sos.php', {ride_id: currentRideId});
    Toast.error('SOS Sent!', 'Emergency alert sent. Help is on the way!');
  }
}

// ── Inject driver bobbing animation CSS ──────────────────────────
var style = document.createElement('style');
style.textContent = '@keyframes driverBob { from{transform:translateY(0) rotate(-5deg)} to{transform:translateY(-4px) rotate(5deg)} }';
document.head.appendChild(style);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
