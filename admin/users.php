<?php
$pageTitle = 'Manage Riders';
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireAdminLogin();

$db = Database::getInstance();
$search = sanitize($_GET['search'] ?? '');
$where = $search ? "WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ?" : "";
$params = $search ? ["%$search%","%$search%","%$search%"] : [];
$users = $db->fetchAll("SELECT * FROM users $where ORDER BY created_at DESC LIMIT 50", $params);

// Toggle status
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_user'])) {
    $uid = (int)$_POST['user_id']; $cur = $_POST['current_status'];
    $new = $cur === 'active' ? 'suspended' : 'active';
    $db->execute("UPDATE users SET status=? WHERE id=?", [$new, $uid]);
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
      <a href="<?= APP_URL ?>/admin/users.php" class="active"><i class="fas fa-users"></i> Riders</a>
      <a href="<?= APP_URL ?>/admin/drivers.php"><i class="fas fa-motorcycle"></i> Drivers</a>
      <a href="<?= APP_URL ?>/admin/transactions.php"><i class="fas fa-wallet"></i> Transactions</a>
      <a href="<?= APP_URL ?>/admin/promo_codes.php"><i class="fas fa-tags"></i> Promo Codes</a>
    </nav>
    <div class="sidebar-footer"><a href="<?= APP_URL ?>/admin/logout.php" class="btn btn-ghost btn-sm btn-block"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
  </div>
  <div class="main-content">
    <div class="flex-between mb-3 flex-wrap gap-2">
      <div><h2>Riders</h2><p style="color:var(--text-muted);">Manage all registered riders</p></div>
    </div>
    <div class="card mb-3">
      <form method="GET" style="display:flex;gap:.8rem;">
        <div class="input-group" style="flex:1;"><i class="fas fa-search input-icon"></i>
        <input type="text" name="search" class="form-control" placeholder="Search by name, email or phone..." value="<?= htmlspecialchars($search) ?>"></div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
        <?php if ($search): ?><a href="?" class="btn btn-ghost">Clear</a><?php endif; ?>
      </form>
    </div>
    <div class="card fade-in">
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Rides</th><th>Rating</th><th>Wallet</th><th>Status</th><th>Joined</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td><?= $u['id'] ?></td>
              <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
              <td style="font-size:.82rem;"><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['phone']) ?></td>
              <td><?= $u['total_rides'] ?></td>
              <td style="color:var(--warning);">★ <?= number_format($u['rating'],1) ?></td>
              <td style="color:var(--success);"><?= formatCurrency($u['wallet_balance']) ?></td>
              <td><span class="badge badge-<?= getStatusBadge($u['status']) ?>"><?= ucfirst($u['status']) ?></span></td>
              <td style="font-size:.78rem;color:var(--text-muted);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <input type="hidden" name="current_status" value="<?= $u['status'] ?>">
                  <button type="submit" name="toggle_user" class="btn btn-sm <?= $u['status']==='active'?'btn-ghost':'btn-primary' ?>" onclick="return confirm('Toggle user status?')">
                    <?= $u['status']==='active'?'Suspend':'Activate' ?>
                  </button>
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
