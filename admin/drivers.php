<?php
$pageTitle = 'Manage Drivers';
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireAdminLogin();
$db = Database::getInstance();
$search = sanitize($_GET['search'] ?? '');
$where  = $search ? "WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR vehicle_number LIKE ?" : "";
$params = $search ? ["%$search%","%$search%","%$search%","%$search%"] : [];
$drivers = $db->fetchAll("SELECT * FROM drivers $where ORDER BY created_at DESC LIMIT 50", $params);
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_driver'])) {
    $did = (int)$_POST['driver_id']; $cur = $_POST['current_status'];
    $db->execute("UPDATE drivers SET status=? WHERE id=?", [$cur==='active'?'suspended':'active', $did]);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}
require_once __DIR__ . '/../includes/header.php';
?>
<div style="display:flex;min-height:100vh;">
  <div id="sidebar" class="sidebar">
    <div class="sidebar-logo"><div class="bolt" style="width:24px;height:24px;"></div><span><span class="flash">Flash</span>Admin</span></div>
    <nav class="sidebar-nav">
      <a href="<?= APP_URL ?>/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="<?= APP_URL ?>/admin/rides.php"><i class="fas fa-route"></i> All Rides</a>
      <a href="<?= APP_URL ?>/admin/users.php"><i class="fas fa-users"></i> Riders</a>
      <a href="<?= APP_URL ?>/admin/drivers.php" class="active"><i class="fas fa-motorcycle"></i> Drivers</a>
      <a href="<?= APP_URL ?>/admin/transactions.php"><i class="fas fa-wallet"></i> Transactions</a>
      <a href="<?= APP_URL ?>/admin/promo_codes.php"><i class="fas fa-tags"></i> Promo Codes</a>
    </nav>
    <div class="sidebar-footer"><a href="<?= APP_URL ?>/admin/logout.php" class="btn btn-ghost btn-sm btn-block"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
  </div>
  <div class="main-content">
    <div class="flex-between mb-3"><div><h2>Drivers</h2><p style="color:var(--text-muted);">Manage all registered drivers</p></div></div>
    <div class="card mb-3"><form method="GET" style="display:flex;gap:.8rem;">
      <div class="input-group" style="flex:1;"><i class="fas fa-search input-icon"></i>
      <input type="text" name="search" class="form-control" placeholder="Search drivers..." value="<?= htmlspecialchars($search) ?>"></div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
      <?php if ($search): ?><a href="?" class="btn btn-ghost">Clear</a><?php endif; ?>
    </form></div>
    <div class="card fade-in">
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Name</th><th>Vehicle</th><th>Type</th><th>Number</th><th>Rides</th><th>Rating</th><th>Earnings</th><th>Status</th><th>Available</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($drivers as $d): ?>
            <tr>
              <td><?= $d['id'] ?></td>
              <td><strong><?= htmlspecialchars($d['full_name']) ?></strong><div style="font-size:.75rem;color:var(--text-muted);"><?= htmlspecialchars($d['email']) ?></div></td>
              <td><?= htmlspecialchars($d['vehicle_model'] ?? 'N/A') ?></td>
              <td><?= ['bike'=>'🏍️','auto'=>'🛺','cab'=>'🚗'][$d['vehicle_type']] ?? '' ?> <?= ucfirst($d['vehicle_type']) ?></td>
              <td style="font-family:monospace;"><?= htmlspecialchars($d['vehicle_number']) ?></td>
              <td><?= $d['total_rides'] ?></td>
              <td style="color:var(--warning);">★ <?= number_format($d['rating'],1) ?></td>
              <td style="color:var(--success);"><?= formatCurrency($d['wallet_balance']) ?></td>
              <td><span class="badge badge-<?= getStatusBadge($d['status']) ?>"><?= ucfirst($d['status']) ?></span></td>
              <td><span class="badge badge-<?= $d['is_available']?'success':'danger' ?>"><?= $d['is_available']?'Online':'Offline' ?></span></td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="driver_id" value="<?= $d['id'] ?>">
                  <input type="hidden" name="current_status" value="<?= $d['status'] ?>">
                  <button type="submit" name="toggle_driver" class="btn btn-sm <?= $d['status']==='active'?'btn-ghost':'btn-primary' ?>" onclick="return confirm('Toggle driver status?')"><?= $d['status']==='active'?'Suspend':'Activate' ?></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
