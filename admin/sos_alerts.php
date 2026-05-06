<?php
$pageTitle = 'SOS Alerts';
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireAdminLogin();
$db = Database::getInstance();
if (isset($_GET['resolve'])) {
    $db->execute("UPDATE sos_alerts SET status='resolved' WHERE id=?", [(int)$_GET['resolve']]);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}
$alerts = $db->fetchAll("SELECT s.*, u.full_name, u.phone, r.ride_code, r.pickup_address, d.full_name as driver_name FROM sos_alerts s JOIN users u ON s.user_id=u.id JOIN rides r ON s.ride_id=r.id LEFT JOIN drivers d ON r.driver_id=d.id ORDER BY s.created_at DESC LIMIT 50");
require_once __DIR__ . '/../includes/header.php';
?>
<div style="display:flex;min-height:100vh;">
  <div id="sidebar" class="sidebar">
    <div class="sidebar-logo"><div class="bolt" style="width:24px;height:24px;"></div><span><span class="flash">Flash</span>Admin</span></div>
    <nav class="sidebar-nav">
      <a href="<?= APP_URL ?>/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="<?= APP_URL ?>/admin/rides.php"><i class="fas fa-route"></i> All Rides</a>
      <a href="<?= APP_URL ?>/admin/users.php"><i class="fas fa-users"></i> Riders</a>
      <a href="<?= APP_URL ?>/admin/drivers.php"><i class="fas fa-motorcycle"></i> Drivers</a>
      <a href="<?= APP_URL ?>/admin/transactions.php"><i class="fas fa-wallet"></i> Transactions</a>
      <a href="<?= APP_URL ?>/admin/sos_alerts.php" class="active"><i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i> SOS Alerts</a>
    </nav>
    <div class="sidebar-footer"><a href="<?= APP_URL ?>/admin/logout.php" class="btn btn-ghost btn-sm btn-block"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
  </div>
  <div class="main-content">
    <div class="page-header"><h2 style="color:var(--danger)"><i class="fas fa-exclamation-triangle"></i> SOS Alerts</h2><p style="color:var(--text-muted);">Emergency alerts requiring immediate attention</p></div>
    <div class="card fade-in">
      <div class="table-wrap">
        <table>
          <thead><tr><th>User</th><th>Phone</th><th>Ride Code</th><th>Driver</th><th>Pickup</th><th>Message</th><th>Status</th><th>Time</th><th>Action</th></tr></thead>
          <tbody>
            <?php if (empty($alerts)): ?>
            <tr><td colspan="9" style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-shield-alt" style="font-size:2rem;display:block;margin-bottom:.8rem;color:var(--success)"></i>No SOS alerts. All clear!</td></tr>
            <?php else: foreach ($alerts as $a): ?>
            <tr style="<?= $a['status']==='active'?'background:rgba(255,59,107,0.05)':'' ?>">
              <td><strong><?= htmlspecialchars($a['full_name']) ?></strong></td>
              <td><a href="tel:<?= htmlspecialchars($a['phone']) ?>" style="color:var(--primary);"><?= htmlspecialchars($a['phone']) ?></a></td>
              <td style="font-family:monospace;color:var(--primary);"><?= htmlspecialchars($a['ride_code']) ?></td>
              <td><?= htmlspecialchars($a['driver_name'] ?? 'N/A') ?></td>
              <td style="font-size:.8rem;max-width:160px;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars(substr($a['pickup_address'],0,40)) ?>...</td>
              <td style="font-size:.82rem;"><?= htmlspecialchars($a['message']) ?></td>
              <td><span class="badge badge-<?= $a['status']==='active'?'danger':'success' ?>"><?= ucfirst($a['status']) ?></span></td>
              <td style="font-size:.78rem;color:var(--text-muted);"><?= timeAgo($a['created_at']) ?></td>
              <td>
                <?php if ($a['status']==='active'): ?>
                <a href="?resolve=<?= $a['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Mark as resolved?')"><i class="fas fa-check"></i> Resolve</a>
                <?php else: ?><span style="color:var(--success);font-size:.82rem;">✓ Resolved</span><?php endif; ?>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
