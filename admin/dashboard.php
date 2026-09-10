<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$pageTitle = 'Admin Dashboard';

$stats = [];
$stats['users'] = $pdo->query("SELECT COUNT(*) c FROM users WHERE role='customer'")->fetch()['c'];
$stats['owners'] = $pdo->query("SELECT COUNT(*) c FROM users WHERE role='owner'")->fetch()['c'];
$stats['vehicles'] = $pdo->query("SELECT COUNT(*) c FROM vehicles")->fetch()['c'];
$stats['bookings'] = $pdo->query("SELECT COUNT(*) c FROM bookings")->fetch()['c'];
$stats['revenue'] = $pdo->query("SELECT SUM(amount) s FROM payments WHERE status='success'")->fetch()['s'] ?: 0;
$stats['pending_kyc'] = $pdo->query("SELECT COUNT(*) c FROM kyc_documents WHERE status='pending'")->fetch()['c'];

// revenue by month (last 6 months)
$stmt = $pdo->query("SELECT DATE_FORMAT(paid_at,'%b %Y') ym, SUM(amount) total FROM payments WHERE status='success' GROUP BY YEAR(paid_at), MONTH(paid_at) ORDER BY paid_at DESC LIMIT 6");
$monthly = array_reverse($stmt->fetchAll());

require_once __DIR__ . '/../includes/header.php';
?>
<h2>Admin Dashboard</h2>
<div class="grid grid-4">
    <div class="stat-card"><div class="num"><?php echo $stats['users']; ?></div><div class="label">Customers</div></div>
    <div class="stat-card"><div class="num"><?php echo $stats['owners']; ?></div><div class="label">Vehicle Owners</div></div>
    <div class="stat-card"><div class="num"><?php echo $stats['vehicles']; ?></div><div class="label">Total Vehicles</div></div>
    <div class="stat-card"><div class="num"><?php echo $stats['bookings']; ?></div><div class="label">Total Bookings</div></div>
    <div class="stat-card"><div class="num"><?php echo money($stats['revenue']); ?></div><div class="label">Total Revenue</div></div>
    <div class="stat-card"><div class="num"><?php echo $stats['pending_kyc']; ?></div><div class="label">Pending KYC</div></div>
</div>

<div class="card" style="margin-top:20px;">
    <h3>Revenue (last 6 months)</h3>
    <table>
        <tr><th>Month</th><th>Revenue</th></tr>
        <?php foreach ($monthly as $m): ?>
        <tr><td><?php echo e($m['ym']); ?></td><td><?php echo money($m['total']); ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$monthly): ?><tr><td colspan="2" class="muted">No revenue data yet.</td></tr><?php endif; ?>
    </table>
</div>

<div class="grid grid-4" style="margin-top:20px;">
    <a class="card" href="users.php"><h3>👥 Manage Users</h3></a>
    <a class="card" href="vehicles.php"><h3>🚗 Manage Vehicles</h3></a>
    <a class="card" href="bookings.php"><h3>📑 Manage Bookings</h3></a>
    <a class="card" href="kyc.php"><h3>🪪 KYC Verification</h3></a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
