<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireAdminLogin();

$db = Database::getInstance();
$totalUsers   = $db->fetch("SELECT COUNT(*) as cnt FROM users WHERE status='active'")['cnt'];
$totalDrivers = $db->fetch("SELECT COUNT(*) as cnt FROM drivers WHERE status='active'")['cnt'];
$totalRides   = $db->fetch("SELECT COUNT(*) as cnt FROM rides")['cnt'];
$todayRides   = $db->fetch("SELECT COUNT(*) as cnt FROM rides WHERE DATE(requested_at)=CURDATE()")['cnt'];
$revenue      = $db->fetch("SELECT COALESCE(SUM(final_fare),0) as total FROM rides WHERE status='completed'")['total'];
$todayRevenue = $db->fetch("SELECT COALESCE(SUM(final_fare),0) as total FROM rides WHERE status='completed' AND DATE(completed_at)=CURDATE()")['total'];
$activeRides  = $db->fetch("SELECT COUNT(*) as cnt FROM rides WHERE status IN ('searching','accepted','arrived','started')")['cnt'];
$onlineDrivers= $db->fetch("SELECT COUNT(*) as cnt FROM drivers WHERE is_available=1 AND status='active'")['cnt'];
$recentRides  = $db->fetchAll("SELECT r.*, u.full_name as user_name, d.full_name as driver_name, rc.name as cat_name FROM rides r JOIN users u ON r.user_id=u.id LEFT JOIN drivers d ON r.driver_id=d.id JOIN ride_categories rc ON r.category_id=rc.id ORDER BY r.requested_at DESC LIMIT 10");
$sosAlerts    = $db->fetchAll("SELECT s.*, u.full_name, r.ride_code FROM sos_alerts s JOIN users u ON s.user_id=u.id JOIN rides r ON s.ride_id=r.id WHERE s.status='active' ORDER BY s.created_at DESC LIMIT 5");

// Last 7 days ride data for chart
$chartData = $db->fetchAll("SELECT DATE(requested_at) as day, COUNT(*) as rides, COALESCE(SUM(CASE WHEN status='completed' THEN final_fare ELSE 0 END),0) as revenue FROM rides WHERE requested_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(requested_at) ORDER BY day");

require_once __DIR__ . '/../includes/header.php';
?>
<div style="display:flex;min-height:100vh;">
  <!-- Sidebar -->
  <div id="sidebar" class="sidebar">
    <div class="sidebar-logo"><div class="bolt" style="width:24px;height:24px;"></div><span><span class="flash">Flash</span>Admin</span></div>
    <nav class="sidebar-nav">
      <div class="sidebar-section">Main</div>
      <a href="<?= APP_URL ?>/admin/dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="<?= APP_URL ?>/admin/rides.php"><i class="fas fa-route"></i> All Rides</a>
      <div class="sidebar-section">Users</div>
      <a href="<?= APP_URL ?>/admin/users.php"><i class="fas fa-users"></i> Riders</a>
      <a href="<?= APP_URL ?>/admin/drivers.php"><i class="fas fa-motorcycle"></i> Drivers</a>
      <div class="sidebar-section">Finance</div>
      <a href="<?= APP_URL ?>/admin/transactions.php"><i class="fas fa-wallet"></i> Transactions</a>
      <a href="<?= APP_URL ?>/admin/promo_codes.php"><i class="fas fa-tags"></i> Promo Codes</a>
      <div class="sidebar-section">Safety</div>
      <a href="<?= APP_URL ?>/admin/sos_alerts.php"><i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i> SOS Alerts <?php if (count($sosAlerts)): ?><span style="background:var(--danger);border-radius:50%;width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;font-size:.7rem;margin-left:auto;"><?= count($sosAlerts) ?></span><?php endif; ?></a>
    </nav>
    <div class="sidebar-footer">
      <a href="<?= APP_URL ?>/admin/logout.php" class="btn btn-ghost btn-sm btn-block"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>

  <div class="main-content">
    <div class="flex-between mb-4 flex-wrap gap-2">
      <div>
        <h2>Dashboard</h2>
        <p style="color:var(--text-muted);">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>! Here's what's happening.</p>
      </div>
      <div style="display:flex;gap:.5rem;align-items:center;">
        <span style="font-size:.82rem;color:var(--text-muted);"><i class="fas fa-circle" style="color:var(--success)"></i> <?= $onlineDrivers ?> drivers online</span>
        <span class="badge badge-<?= $activeRides > 0 ? 'warning' : 'success' ?>"><?= $activeRides ?> active rides</span>
      </div>
    </div>

    <!-- SOS Alerts -->
    <?php if (!empty($sosAlerts)): ?>
    <div class="alert alert-danger mb-3" style="font-size:.9rem;">
      <i class="fas fa-exclamation-triangle"></i>
      <div>
        <strong><?= count($sosAlerts) ?> Active SOS Alert(s)!</strong>
        <?php foreach ($sosAlerts as $s): ?>
        <div>Ride <?= htmlspecialchars($s['ride_code']) ?> — <?= htmlspecialchars($s['full_name']) ?> — <?= timeAgo($s['created_at']) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid fade-in">
      <div class="stat-card"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-num"><?= number_format($totalUsers) ?></div><div class="stat-lbl">Total Riders</div></div>
      <div class="stat-card"><div class="stat-icon"><i class="fas fa-motorcycle"></i></div><div class="stat-num"><?= number_format($totalDrivers) ?></div><div class="stat-lbl">Total Drivers</div></div>
      <div class="stat-card"><div class="stat-icon"><i class="fas fa-route"></i></div><div class="stat-num"><?= number_format($totalRides) ?></div><div class="stat-lbl">Total Rides</div><div class="stat-change up">+<?= $todayRides ?> today</div></div>
      <div class="stat-card"><div class="stat-icon"><i class="fas fa-rupee-sign"></i></div><div class="stat-num" style="color:var(--success)">₹<?= number_format($revenue,0) ?></div><div class="stat-lbl">Total Revenue</div><div class="stat-change up">+₹<?= number_format($todayRevenue,0) ?> today</div></div>
    </div>

    <!-- Charts Row -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;" class="fade-in">
      <div class="card">
        <div class="card-header flex-between">
          <h4 class="card-title">Rides (Last 7 Days)</h4>
        </div>
        <canvas id="ridesChart" height="200"></canvas>
      </div>
      <div class="card">
        <div class="card-header flex-between">
          <h4 class="card-title">Revenue (Last 7 Days)</h4>
        </div>
        <canvas id="revenueChart" height="200"></canvas>
      </div>
    </div>

    <!-- Recent Rides -->
    <div class="card fade-in">
      <div class="card-header flex-between">
        <h4 class="card-title">Recent Rides</h4>
        <a href="<?= APP_URL ?>/admin/rides.php" style="font-size:.85rem;color:var(--primary);">View All →</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Code</th><th>Rider</th><th>Driver</th><th>Category</th><th>Fare</th><th>Status</th><th>Time</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($recentRides as $r): ?>
            <tr>
              <td><span style="font-family:var(--font-display);font-weight:700;color:var(--primary);font-size:.8rem;"><?= htmlspecialchars($r['ride_code']) ?></span></td>
              <td><?= htmlspecialchars($r['user_name']) ?></td>
              <td><?= htmlspecialchars($r['driver_name'] ?? 'Unassigned') ?></td>
              <td><?= htmlspecialchars($r['cat_name']) ?></td>
              <td style="font-weight:700;"><?= formatCurrency($r['final_fare'] ?? $r['estimated_fare']) ?></td>
              <td><span class="badge badge-<?= getStatusBadge($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
              <td style="font-size:.78rem;color:var(--text-muted);"><?= timeAgo($r['requested_at']) ?></td>
              <td>
                <a href="<?= APP_URL ?>/pages/track_ride.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-ghost" target="_blank"><i class="fas fa-eye"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const chartData = <?= json_encode($chartData) ?>;
const labels = chartData.map(d => d.day);
const rides   = chartData.map(d => parseInt(d.rides));
const revenue = chartData.map(d => parseFloat(d.revenue));

const commonOptions = {
  responsive: true,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9898B8', font: { size: 11 } } },
    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9898B8', font: { size: 11 } } }
  }
};

new Chart(document.getElementById('ridesChart'), {
  type: 'bar',
  data: { labels, datasets: [{ data: rides, backgroundColor: 'rgba(255,107,0,0.7)', borderRadius: 6, hoverBackgroundColor: '#FF6B00' }] },
  options: commonOptions
});

new Chart(document.getElementById('revenueChart'), {
  type: 'line',
  data: { labels, datasets: [{ data: revenue, borderColor: '#00E676', backgroundColor: 'rgba(0,230,118,0.1)', tension: 0.4, fill: true, pointBackgroundColor: '#00E676' }] },
  options: commonOptions
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
