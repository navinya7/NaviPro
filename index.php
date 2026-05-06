<?php
// ============================================================
// FlashRide — Landing Page
// ============================================================
$pageTitle = 'Home';
require_once __DIR__ . '/includes/functions.php';
startSession();
$isLoggedIn = isUserLoggedIn();

// Load ride categories — safely
$categories = [];
try {
    $categories = Database::getInstance()->fetchAll("SELECT * FROM ride_categories WHERE is_active = 1 ORDER BY id");
} catch (Exception $e) {
    // DB not set up yet — show landing page anyway
}

require_once __DIR__ . '/includes/header.php';
?>

<div id="toast-container"></div>

<!-- ══ NAVBAR ══════════════════════════════════════════════ -->
<nav class="navbar">
  <a href="<?= APP_URL ?>" class="navbar-brand">
    <div class="bolt"></div>
    <span><span class="flash">Flash</span>Ride</span>
  </a>
  <div class="nav-links">
    <a href="#features">Features</a>
    <a href="#how-it-works">How It Works</a>
    <a href="#pricing">Pricing</a>
    <?php if ($isLoggedIn): ?>
      <a href="<?= APP_URL ?>/pages/dashboard.php" class="nav-btn">Dashboard</a>
      <a href="<?= APP_URL ?>/pages/logout.php" class="nav-btn outline">Logout</a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/pages/user_login.php" class="nav-btn outline">Log In</a>
      <a href="<?= APP_URL ?>/pages/user_register.php" class="nav-btn">Book a Ride</a>
    <?php endif; ?>
  </div>
  <button id="sidebarToggle" style="display:none;background:none;border:none;color:var(--text);font-size:1.4rem;cursor:pointer;padding:.5rem;">
    <i class="fas fa-bars"></i>
  </button>
</nav>

<!-- ══ HERO ════════════════════════════════════════════════ -->
<section class="hero">
  <div class="hero-grid">
    <div class="hero-content fade-in">
      <div class="hero-tag"><i class="fas fa-bolt"></i>&nbsp; India's Fastest Ride Platform</div>
      <h1>Ride Smart,<br><span>Arrive Fast</span></h1>
      <p class="hero-subtitle">Book bikes, autos, and cabs in seconds. FlashRide connects you with verified drivers for safe, affordable journeys across the city.</p>
      <div class="hero-actions">
        <a href="<?= $isLoggedIn ? APP_URL.'/pages/book_ride.php' : APP_URL.'/pages/user_register.php' ?>" class="btn btn-primary btn-lg">
          <i class="fas fa-bolt"></i> Book Ride Now
        </a>
        <a href="<?= APP_URL ?>/pages/driver_register.php" class="btn btn-outline btn-lg">
          <i class="fas fa-motorcycle"></i> Become a Driver
        </a>
      </div>
      <div class="hero-stats">
        <div class="hero-stat"><div class="num">50K+</div><div class="lbl">Rides Daily</div></div>
        <div class="hero-stat"><div class="num">8K+</div><div class="lbl">Drivers</div></div>
        <div class="hero-stat"><div class="num">4.8★</div><div class="lbl">Rating</div></div>
      </div>
    </div>
    <div class="hero-visual">
      <div class="phone-glow"></div>
      <div class="phone-mockup">
        <div id="heroMapContainer" style="width:100%;height:100%;border-radius:42px;overflow:hidden;"></div>
      </div>
    </div>
  </div>
</section>

<!-- ══ FEATURES ════════════════════════════════════════════ -->
<section class="section" id="features">
  <div class="section-header" data-animate>
    <div class="section-tag"><i class="fas fa-star"></i>&nbsp; Why FlashRide</div>
    <h2>Everything You Need in One App</h2>
    <p>Built with safety, speed, and comfort in mind — for every Indian city commuter</p>
  </div>
  <div class="features-grid">
    <?php
    $feats = [
      ['fas fa-map-marked-alt','Live GPS Tracking','Track your ride in real-time. Know exactly where your driver is at all times.'],
      ['fas fa-shield-alt','Safety First','Emergency SOS button, ride sharing with contacts, and 24/7 support.'],
      ['fas fa-bolt','Instant Booking','Get a driver assigned within 60 seconds. No waiting, no hassle.'],
      ['fas fa-wallet','FlashWallet','Store money securely, earn cashback, and pay without touching cash.'],
      ['fas fa-tags','Smart Pricing','No hidden charges. Fair pricing with promo codes for extra savings.'],
      ['fas fa-star','Verified Drivers','All drivers pass background checks, vehicle inspection, and training.'],
    ];
    foreach ($feats as $f): ?>
    <div class="feature-card" data-animate>
      <div class="feature-icon"><i class="<?= $f[0] ?>"></i></div>
      <h3><?= $f[1] ?></h3>
      <p><?= $f[2] ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══ RIDE TYPES ══════════════════════════════════════════ -->
<section class="section" style="background:var(--dark);" id="pricing">
  <div class="section-header" data-animate>
    <div class="section-tag"><i class="fas fa-motorcycle"></i>&nbsp; Fleet</div>
    <h2>Choose Your Ride</h2>
    <p>From solo bike rides to shared cabs — something for every budget</p>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.2rem;max-width:1000px;margin:0 auto;">
    <?php
    if (!empty($categories)):
      $icons = ['bike'=>'🏍️','auto'=>'🛺','cab'=>'🚗'];
      foreach ($categories as $cat):
        $icon = $icons[$cat['type']] ?? '🚗'; ?>
    <div class="card" style="text-align:center;padding:2rem 1.2rem;" data-animate>
      <div style="font-size:2.8rem;margin-bottom:.8rem;"><?= $icon ?></div>
      <h3 style="font-size:1rem;"><?= htmlspecialchars($cat['name']) ?></h3>
      <p style="color:var(--text-muted);font-size:.82rem;margin:.4rem 0 .8rem;"><?= htmlspecialchars($cat['description']) ?></p>
      <div style="background:var(--dark-3);border-radius:var(--radius-sm);padding:.7rem;">
        <div style="font-size:1.4rem;font-weight:800;color:var(--primary);font-family:var(--font-display)">₹<?= number_format($cat['base_fare'],0) ?></div>
        <div style="font-size:.72rem;color:var(--text-muted)">Base + ₹<?= $cat['per_km_rate'] ?>/km</div>
      </div>
    </div>
    <?php endforeach;
    else: ?>
    <div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--text-muted);">
      <p>Pricing information will appear after database setup.</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ══ HOW IT WORKS ════════════════════════════════════════ -->
<section class="section" id="how-it-works">
  <div class="section-header" data-animate>
    <div class="section-tag"><i class="fas fa-route"></i>&nbsp; Process</div>
    <h2>How FlashRide Works</h2>
    <p>Get from A to B in 4 simple steps</p>
  </div>
  <div class="steps-grid">
    <?php foreach ([
      ['Enter Location','Open the app, type your pickup and drop-off location.'],
      ['Choose Ride','Select from bike, auto, or cab based on your budget.'],
      ['Driver Assigned','A verified driver near you accepts in seconds.'],
      ['Enjoy the Ride','Track live, pay securely, and rate your experience.'],
    ] as $i => $s): ?>
    <div class="step-item" data-animate>
      <div class="step-num"><?= $i+1 ?></div>
      <h4><?= $s[0] ?></h4>
      <p><?= $s[1] ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══ TESTIMONIALS ════════════════════════════════════════ -->
<section class="section" style="background:var(--dark);">
  <div class="section-header" data-animate>
    <div class="section-tag"><i class="fas fa-heart"></i>&nbsp; Testimonials</div>
    <h2>Loved by Thousands</h2>
  </div>
  <div class="testimonials-grid">
    <?php foreach ([
      ['Ananya Mehta','Regular Commuter','Best ride app I\'ve used. Drivers are always on time and the app is super smooth!','5'],
      ['Rohit Joshi','College Student','FlashRide saved me so much money with wallet cashback. Bike rides are super fast!','5'],
      ['Neha Kapoor','Working Professional','The SOS feature and live tracking give me peace of mind every single trip.','4'],
    ] as $r): ?>
    <div class="testimonial-card" data-animate>
      <div class="testimonial-stars"><?= str_repeat('★',(int)$r[3]) ?></div>
      <p class="testimonial-text">"<?= $r[2] ?>"</p>
      <div class="testimonial-user">
        <div class="testimonial-avatar"><?= $r[0][0] ?></div>
        <div><div class="testimonial-name"><?= $r[0] ?></div><div class="testimonial-role"><?= $r[1] ?></div></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══ CTA ═════════════════════════════════════════════════ -->
<section class="section">
  <div class="cta-section" data-animate>
    <div style="position:relative;z-index:1;">
      <div class="section-tag" style="margin-bottom:1rem;">Limited Time Offer</div>
      <h2>Your First Ride is <span style="color:var(--primary)">FREE!</span></h2>
      <p style="color:var(--text-secondary);margin:1rem 0 2rem;">Sign up and use code <strong style="color:var(--accent)">WELCOME100</strong> for ₹100 off your first ride.</p>
      <a href="<?= APP_URL ?>/pages/user_register.php" class="btn btn-primary btn-lg">
        <i class="fas fa-rocket"></i> Start Riding Free
      </a>
    </div>
  </div>
</section>

<!-- ══ FOOTER ══════════════════════════════════════════════ -->
<footer class="footer">
  <div class="footer-grid">
    <div class="footer-brand">
      <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:800;"><span style="color:var(--primary)">Flash</span>Ride</div>
      <p>India's fastest and most trusted ride-booking platform. Safe, affordable, and always on time.</p>
      <div class="social-links mt-2">
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-facebook"></i></a>
        <a href="#"><i class="fab fa-youtube"></i></a>
      </div>
    </div>
    <div class="footer-col">
      <h5>Riders</h5>
      <a href="<?= APP_URL ?>/pages/user_register.php">Sign Up</a>
      <a href="<?= APP_URL ?>/pages/book_ride.php">Book a Ride</a>
      <a href="<?= APP_URL ?>/pages/ride_history.php">Ride History</a>
      <a href="#">Promo Codes</a>
    </div>
    <div class="footer-col">
      <h5>Drivers</h5>
      <a href="<?= APP_URL ?>/pages/driver_register.php">Become a Driver</a>
      <a href="<?= APP_URL ?>/pages/driver_login.php">Driver Login</a>
      <a href="#">Earnings Guide</a>
    </div>
    <div class="footer-col">
      <h5>Company</h5>
      <a href="#">About Us</a>
      <a href="#">Safety</a>
      <a href="#">Careers</a>
      <a href="#">Contact</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© 2025 FlashRide Technologies Pvt. Ltd. All rights reserved.</p>
    <p>Made with ❤️ in India</p>
  </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Hero demo map
  try {
    var heroMap = MapHelper.initMap('heroMapContainer', 18.5204, 73.8567, 12);
    MapHelper.addMarker(18.5204, 73.8567, 'pickup', 'Pickup: Pune Station');
    MapHelper.addMarker(18.5329, 73.8476, 'drop',   'Drop: Shivajinagar');
    MapHelper.addMarker(18.5260, 73.8520, 'driver',  'Driver: Suresh');
    MapHelper.drawRoute([[18.5204,73.8567],[18.5240,73.8540],[18.5260,73.8520],[18.5329,73.8476]]);
  } catch(e) { console.warn('Map init error:', e); }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
