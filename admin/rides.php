<?php
$pageTitle = 'All Rides';
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireAdminLogin();
$db = Database::getInstance();
$status = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$where = "WHERE 1=1";
$params = [];
if ($status) { $where .= " AND r.status=?"; $params[] = $status; }
if ($search) { $where .= " AND (r.ride_code LIKE ? OR u.full_name LIKE ? OR d.full_name LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
$rides = $db->fetchAll("SELECT r.*, u.full_name as user_name, d.full_name as driver_name, rc.name as cat_name FROM rides r JOIN users u ON r.user_id=u.id LEFT JOIN drivers d ON r.driver_id=d.id JOIN ride_categories rc ON r.category_id=rc.id $where ORDER BY r.requested_at DESC LIMIT 50", $params);
require_once __DIR__ . '/../includes/header.php';
?>
<div style="display:flex;min-height:100vh;">
  <div id="sidebar" class="sidebar">
    <div class="sidebar-logo"><div class="bolt" style="width:24px;height:24px;"></div><span><span class="flash">Flash</span>Admin</span></div>
    <nav class="sidebar-nav">
      <a href="<?= APP_URL ?>/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="<?= APP_URL ?>/admin/rides.php" class="active"><i class="fas fa-route"></i> All Rides</a>
      <a href="<?= APP_URL ?>/admin/users.php"><i class="fas fa-users"></i> Riders</a>
      <a href="<?= APP_URL ?>/admin/drivers.php"><i class="fas fa-motorcycle"></i> Drivers</a>
      <a href="<?= APP_URL ?>/admin/transactions.php"><i class="fas fa-wallet"></i> Transactions</a>
      <a href="<?= APP_URL ?>/admin/promo_codes.php"><i class="fas fa-tags"></i> Promo Codes</a>
    </nav>
    <div class="sidebar-footer"><a href="<?= APP_URL ?>/admin/logout.php" class="btn btn-ghost btn-sm btn-block"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
  </div>
  <div class="main-content">
    <div class="flex-between mb-3"><div><h2>All Rides</h2><p style="color:var(--text-muted);">Monitor and manage every ride</p></div></div>
    <div class="card mb-3">
      <form method="GET" style="display:flex;gap:.8rem;flex-wrap:wrap;">
        <div class="input-group" style="flex:2;min-width:200px;"><i class="fas fa-search input-icon"></i>
        <input type="text" name="search" class="form-control" placeholder="Search ride code, rider, driver..." value="<?= htmlspecialchars($search) ?>"></div>
        <select name="status" class="form-control" style="flex:1;min-width:140px;">
          <option value="">All Statuses</option>
          <?php foreach (['searching','accepted','arrived','started','completed','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="?" class="btn btn-ghost">Reset</a>
      </form>
    </div>
    <div class="card fade-in">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Code</th><th>Rider</th><th>Driver</th><th>Type</th><th>Distance</th><th>Fare</th><th>Payment</th><th>Status</th><th>Date</th><th>Map</th></tr></thead>
          <tbody>
            <?php if (empty($rides)): ?>
            <tr><td colspan="10" style="text-align:center;padding:3rem;color:var(--text-muted);">No rides found.</td></tr>
            <?php else: foreach ($rides as $r): ?>
            <tr>
              <td><span style="font-family:var(--font-display);color:var(--primary);font-weight:700;font-size:.8rem;"><?= htmlspecialchars($r['ride_code']) ?></span></td>
              <td><?= htmlspecialchars($r['user_name']) ?></td>
              <td><?= htmlspecialchars($r['driver_name'] ?? '<span style="color:var(--text-muted)">Unassigned</span>') ?></td>
              <td><?= htmlspecialchars($r['cat_name']) ?></td>
              <td><?= number_format($r['distance_km'],1) ?> km</td>
              <td style="font-weight:700;"><?= formatCurrency($r['final_fare'] ?? $r['estimated_fare']) ?></td>
              <td><?= ucfirst($r['payment_method']) ?></td>
              <td><span class="badge badge-<?= getStatusBadge($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
              <td style="font-size:.78rem;color:var(--text-muted);"><?= date('d M Y', strtotime($r['requested_at'])) ?></td>
              <td><a href="<?= APP_URL ?>/pages/track_ride.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-ghost"><i class="fas fa-map"></i></a></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
