<?php
$pageTitle = 'Ride History';
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireUserLogin();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$status = sanitize($_GET['status'] ?? '');
$where = "WHERE r.user_id = ?";
$params = [$userId];
if ($status) { $where .= " AND r.status = ?"; $params[] = $status; }
$total = $db->fetch("SELECT COUNT(*) as cnt FROM rides r $where", $params)['cnt'] ?? 0;
$rides = $db->fetchAll("SELECT r.*, rc.name as category_name, rc.type as vehicle_type, d.full_name as driver_name FROM rides r JOIN ride_categories rc ON r.category_id=rc.id LEFT JOIN drivers d ON r.driver_id=d.id $where ORDER BY r.requested_at DESC LIMIT $perPage OFFSET $offset", $params);
$totalPages = ceil($total / $perPage);

require_once __DIR__ . '/../includes/header.php';
?>
<nav class="navbar">
  <a href="<?= APP_URL ?>" class="navbar-brand"><div class="bolt"></div><span><span class="flash">Flash</span>Ride</span></a>
  <div class="nav-links">
    <a href="<?= APP_URL ?>/pages/dashboard.php">Dashboard</a>
    <a href="<?= APP_URL ?>/pages/book_ride.php" class="nav-btn">Book Ride</a>
    <a href="<?= APP_URL ?>/pages/logout.php" class="nav-btn outline">Logout</a>
  </div>
</nav>
<div style="padding:88px 1.5rem 2rem;max-width:1100px;margin:0 auto;">
  <div class="page-header flex-between flex-wrap gap-2">
    <div><h2>Ride History</h2><p>All your past and ongoing rides</p></div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
      <?php foreach (['','searching','completed','cancelled'] as $s): $lbl = $s ?: 'All'; ?>
      <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-ghost' ?>"><?= ucfirst($lbl) ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>Ride</th><th>Route</th><th>Type</th><th>Driver</th><th>Fare</th><th>Status</th><th>Date</th><th>Action</th>
        </tr></thead>
        <tbody>
          <?php if (empty($rides)): ?>
          <tr><td colspan="8" style="text-align:center;padding:3rem;color:var(--text-muted);">
            <i class="fas fa-route" style="font-size:2rem;display:block;margin-bottom:.8rem;opacity:.3;"></i>No rides found.
          </td></tr>
          <?php else: foreach ($rides as $r):
            $icons = ['bike'=>'🏍️','auto'=>'🛺','cab'=>'🚗'];
          ?>
          <tr>
            <td><span style="font-family:var(--font-display);font-weight:700;color:var(--primary);font-size:.82rem;"><?= htmlspecialchars($r['ride_code']) ?></span></td>
            <td style="max-width:200px;">
              <div style="font-size:.8rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text-secondary);"><?= htmlspecialchars(substr($r['pickup_address'],0,30)) ?>...</div>
              <div style="font-size:.8rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars(substr($r['dropoff_address'],0,30)) ?>...</div>
            </td>
            <td><?= $icons[$r['vehicle_type']] ?? '🚗' ?> <?= htmlspecialchars($r['category_name']) ?></td>
            <td><?= htmlspecialchars($r['driver_name'] ?? 'N/A') ?></td>
            <td style="font-weight:700;color:var(--primary);"><?= formatCurrency($r['final_fare'] ?? $r['estimated_fare']) ?></td>
            <td><span class="badge badge-<?= getStatusBadge($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
            <td style="font-size:.8rem;color:var(--text-muted);"><?= date('d M Y', strtotime($r['requested_at'])) ?></td>
            <td>
              <a href="<?= APP_URL ?>/pages/track_ride.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-ghost"><i class="fas fa-eye"></i></a>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div style="display:flex;gap:.5rem;justify-content:center;margin-top:1.5rem;">
      <?php for ($i=1;$i<=$totalPages;$i++): ?>
      <a href="?page=<?= $i ?>&status=<?= $status ?>" class="btn btn-sm <?= $i===$page?'btn-primary':'btn-ghost' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
