<?php
$pageTitle = 'Transactions';
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireAdminLogin();
$db = Database::getInstance();
$txns = $db->fetchAll("SELECT wt.*, CASE WHEN wt.user_type='user' THEN u.full_name ELSE d.full_name END as person_name FROM wallet_transactions wt LEFT JOIN users u ON wt.user_type='user' AND wt.user_id=u.id LEFT JOIN drivers d ON wt.user_type='driver' AND wt.user_id=d.id ORDER BY wt.created_at DESC LIMIT 100");
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
      <a href="<?= APP_URL ?>/admin/transactions.php" class="active"><i class="fas fa-wallet"></i> Transactions</a>
      <a href="<?= APP_URL ?>/admin/promo_codes.php"><i class="fas fa-tags"></i> Promo Codes</a>
    </nav>
    <div class="sidebar-footer"><a href="<?= APP_URL ?>/admin/logout.php" class="btn btn-ghost btn-sm btn-block"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
  </div>
  <div class="main-content">
    <div class="page-header"><h2>Wallet Transactions</h2><p style="color:var(--text-muted);">All money movements across the platform</p></div>
    <div class="card fade-in">
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Person</th><th>Type</th><th>Txn Type</th><th>Amount</th><th>Balance After</th><th>Description</th><th>Date</th></tr></thead>
          <tbody>
            <?php foreach ($txns as $t): ?>
            <tr>
              <td><?= $t['id'] ?></td>
              <td><strong><?= htmlspecialchars($t['person_name'] ?? 'N/A') ?></strong><div style="font-size:.72rem;color:var(--text-muted);"><?= ucfirst($t['user_type']) ?></div></td>
              <td><span class="badge badge-<?= $t['user_type']==='user'?'info':'purple' ?>"><?= ucfirst($t['user_type']) ?></span></td>
              <td><span class="badge badge-<?= $t['type']==='credit'?'success':'danger' ?>"><?= ucfirst($t['type']) ?></span></td>
              <td style="font-weight:700;color:<?= $t['type']==='credit'?'var(--success)':'var(--danger)' ?>;"><?= $t['type']==='credit'?'+':'-' ?><?= formatCurrency($t['amount']) ?></td>
              <td><?= formatCurrency($t['balance_after']) ?></td>
              <td style="font-size:.82rem;"><?= htmlspecialchars($t['description']) ?></td>
              <td style="font-size:.78rem;color:var(--text-muted);"><?= date('d M Y H:i', strtotime($t['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
