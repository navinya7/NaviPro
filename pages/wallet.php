<?php
$pageTitle = 'FlashWallet';
require_once __DIR__ . '/../includes/functions.php';
startSession();
requireUserLogin();

$db     = Database::getInstance();
$userId = (int) $_SESSION['user_id'];
$user   = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]) ?? ['wallet_balance' => 0.00, 'full_name' => 'Guest'];

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_money'])) {
    $amount = (float) ($_POST['amount'] ?? 0);
    if ($amount < 10 || $amount > 10000) {
        $error = 'Amount must be between ₹10 and ₹10,000.';
    } else {
        try {
            creditWallet('user', $userId, $amount, 'Wallet Recharge via UPI/Card');
            $success = number_format($amount, 2) . ' added to your wallet!';
            // Refresh user data
            $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]) ?? $user;
        } catch (Exception $e) {
            $error = 'Could not add money: ' . $e->getMessage();
        }
    }
}

$transactions = $db->fetchAll(
    "SELECT * FROM wallet_transactions WHERE user_type='user' AND user_id=? ORDER BY created_at DESC LIMIT 25",
    [$userId]
);

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

<div style="padding:88px 1.5rem 2rem;max-width:800px;margin:0 auto;">
  <div class="page-header"><h2>FlashWallet</h2><p style="color:var(--text-muted);">Manage your balance and transactions</p></div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> ₹<?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <!-- Balance Card -->
  <div class="wallet-hero fade-in">
    <div class="wallet-label">Available Balance</div>
    <div class="wallet-balance"><?= formatCurrency((float)($user['wallet_balance'] ?? 0)) ?></div>
    <div style="margin-top:.8rem;font-size:.85rem;color:rgba(255,255,255,.8);">
      FlashRide Wallet · <?= htmlspecialchars($user['full_name'] ?? '') ?>
    </div>
  </div>

  <!-- Add Money -->
  <div class="card fade-in mb-3">
    <h4 style="margin-bottom:1.2rem;">Add Money</h4>
    <form method="POST" autocomplete="off">
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem;margin-bottom:1rem;">
        <?php foreach ([100, 200, 500, 1000] as $amt): ?>
        <button type="button"
                onclick="document.getElementById('amountInput').value='<?= $amt ?>'"
                class="btn btn-ghost btn-sm">₹<?= $amt ?></button>
        <?php endforeach; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Custom Amount (₹)</label>
        <div class="input-group">
          <i class="fas fa-rupee-sign input-icon"></i>
          <input type="number" name="amount" id="amountInput"
                 class="form-control" min="10" max="10000" step="1"
                 placeholder="Enter amount (10–10,000)" required>
        </div>
      </div>
      <button type="submit" name="add_money" class="btn btn-primary btn-block btn-lg">
        <i class="fas fa-plus-circle"></i> Add Money
      </button>
      <p style="font-size:.75rem;color:var(--text-muted);text-align:center;margin-top:.8rem;">
        <i class="fas fa-lock"></i> Demo mode — no real payment processed
      </p>
    </form>
  </div>

  <!-- Transactions -->
  <div class="card fade-in">
    <div class="card-header">
      <h4 class="card-title">Transaction History</h4>
    </div>
    <?php if (empty($transactions)): ?>
      <p style="color:var(--text-muted);text-align:center;padding:2rem;">No transactions yet.</p>
    <?php else: foreach ($transactions as $txn): ?>
    <div style="display:flex;align-items:center;gap:1rem;padding:.9rem 0;border-bottom:1px solid var(--border);">
      <div style="width:40px;height:40px;border-radius:10px;background:<?= $txn['type']==='credit'?'var(--success-bg)':'var(--danger-bg)' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="fas fa-arrow-<?= $txn['type']==='credit'?'down':'up' ?>"
           style="color:<?= $txn['type']==='credit'?'var(--success)':'var(--danger)' ?>"></i>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-weight:600;font-size:.88rem;"><?= htmlspecialchars($txn['description']) ?></div>
        <div style="font-size:.75rem;color:var(--text-muted);"><?= date('d M Y, H:i', strtotime($txn['created_at'])) ?></div>
      </div>
      <div style="text-align:right;flex-shrink:0;">
        <div style="font-weight:700;color:<?= $txn['type']==='credit'?'var(--success)':'var(--danger)' ?>;">
          <?= $txn['type']==='credit'?'+':'-' ?><?= formatCurrency((float)$txn['amount']) ?>
        </div>
        <div style="font-size:.75rem;color:var(--text-muted);">Bal: <?= formatCurrency((float)$txn['balance_after']) ?></div>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
