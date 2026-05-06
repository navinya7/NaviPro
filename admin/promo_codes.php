<?php
$pageTitle = 'Promo Codes';
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireAdminLogin();
$db = Database::getInstance();
$promos = $db->fetchAll("SELECT * FROM promo_codes ORDER BY created_at DESC");
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_promo'])) {
    $code   = strtoupper(sanitize($_POST['code'] ?? ''));
    $dtype  = sanitize($_POST['discount_type'] ?? '');
    $dval   = (float)$_POST['discount_value'];
    $minA   = (float)$_POST['min_ride_amount'];
    $maxD   = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
    $limit  = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $from   = sanitize($_POST['valid_from'] ?? '');
    $until  = sanitize($_POST['valid_until'] ?? '');
    if (!$code||!$dtype||!$dval||!$from||!$until) { $error='All required fields must be filled.'; }
    else {
        $db->insert("INSERT INTO promo_codes (code,discount_type,discount_value,min_ride_amount,max_discount,usage_limit,valid_from,valid_until) VALUES (?,?,?,?,?,?,?,?)",
            [$code,$dtype,$dval,$minA,$maxD,$limit,$from,$until]);
        $success='Promo code created!';
        $promos = $db->fetchAll("SELECT * FROM promo_codes ORDER BY created_at DESC");
    }
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
      <a href="<?= APP_URL ?>/admin/drivers.php"><i class="fas fa-motorcycle"></i> Drivers</a>
      <a href="<?= APP_URL ?>/admin/transactions.php"><i class="fas fa-wallet"></i> Transactions</a>
      <a href="<?= APP_URL ?>/admin/promo_codes.php" class="active"><i class="fas fa-tags"></i> Promo Codes</a>
    </nav>
    <div class="sidebar-footer"><a href="<?= APP_URL ?>/admin/logout.php" class="btn btn-ghost btn-sm btn-block"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
  </div>
  <div class="main-content">
    <div class="flex-between mb-3">
      <div><h2>Promo Codes</h2><p style="color:var(--text-muted);">Create and manage discount codes</p></div>
      <button class="btn btn-primary" data-modal="addPromoModal"><i class="fas fa-plus"></i> Add Promo</button>
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <div class="card fade-in">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Code</th><th>Type</th><th>Discount</th><th>Min Fare</th><th>Max Off</th><th>Used/Limit</th><th>Valid Until</th><th>Status</th><th>Toggle</th></tr></thead>
          <tbody>
            <?php foreach ($promos as $p): ?>
            <tr>
              <td><strong style="font-family:monospace;color:var(--primary);"><?= htmlspecialchars($p['code']) ?></strong></td>
              <td><?= ucfirst($p['discount_type']) ?></td>
              <td style="font-weight:700;color:var(--success);"><?= $p['discount_type']==='percentage'?$p['discount_value'].'%':'₹'.$p['discount_value'] ?></td>
              <td>₹<?= number_format($p['min_ride_amount'],0) ?></td>
              <td><?= $p['max_discount'] ? '₹'.$p['max_discount'] : '—' ?></td>
              <td><?= $p['used_count'] ?>/<?= $p['usage_limit'] ?: '∞' ?></td>
              <td style="font-size:.82rem;"><?= date('d M Y', strtotime($p['valid_until'])) ?></td>
              <td><span class="badge badge-<?= $p['is_active']?'success':'danger' ?>"><?= $p['is_active']?'Active':'Inactive' ?></span></td>
              <td>
                <a href="?toggle=<?= $p['id'] ?>&active=<?= $p['is_active']?0:1 ?>" class="btn btn-sm btn-ghost" onclick="return confirm('Toggle promo status?')"><?= $p['is_active']?'Disable':'Enable' ?></a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<!-- Add Promo Modal -->
<div class="modal-overlay" id="addPromoModal">
  <div class="modal">
    <div class="modal-header"><h3>Create Promo Code</h3><button class="modal-close">&times;</button></div>
    <form method="POST">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group" style="grid-column:1/-1"><label class="form-label">Code</label><input type="text" name="code" class="form-control" placeholder="e.g. SAVE30" required style="text-transform:uppercase;"></div>
        <div class="form-group"><label class="form-label">Discount Type</label><select name="discount_type" class="form-control" required><option value="percentage">Percentage %</option><option value="fixed">Fixed ₹</option></select></div>
        <div class="form-group"><label class="form-label">Discount Value</label><input type="number" name="discount_value" class="form-control" step="0.01" required min="1"></div>
        <div class="form-group"><label class="form-label">Min Ride Amount</label><input type="number" name="min_ride_amount" class="form-control" value="0" step="0.01"></div>
        <div class="form-group"><label class="form-label">Max Discount ₹ (optional)</label><input type="number" name="max_discount" class="form-control" step="0.01"></div>
        <div class="form-group"><label class="form-label">Usage Limit (optional)</label><input type="number" name="usage_limit" class="form-control"></div>
        <div class="form-group"><label class="form-label">Valid From</label><input type="date" name="valid_from" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Valid Until</label><input type="date" name="valid_until" class="form-control" required></div>
      </div>
      <button type="submit" name="add_promo" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Create Promo</button>
    </form>
  </div>
</div>
<?php
// Handle toggle
if (isset($_GET['toggle'])) {
    $db->execute("UPDATE promo_codes SET is_active=? WHERE id=?", [(int)$_GET['active'], (int)$_GET['toggle']]);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}
require_once __DIR__ . '/../includes/footer.php'; ?>
