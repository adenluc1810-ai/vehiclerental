<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');
$pageTitle = 'My Dashboard';
$uid = $_SESSION['user_id'];

$stats = [];
$stmt = $pdo->prepare("SELECT COUNT(*) c FROM bookings WHERE customer_id = ?"); $stmt->execute([$uid]); $stats['total'] = $stmt->fetch()['c'];
$stmt = $pdo->prepare("SELECT COUNT(*) c FROM bookings WHERE customer_id = ? AND status IN ('confirmed','ongoing')"); $stmt->execute([$uid]); $stats['active'] = $stmt->fetch()['c'];
$stmt = $pdo->prepare("SELECT COUNT(*) c FROM bookings WHERE customer_id = ? AND status = 'completed'"); $stmt->execute([$uid]); $stats['completed'] = $stmt->fetch()['c'];
$stmt = $pdo->prepare("SELECT SUM(total_amount) s FROM bookings WHERE customer_id = ?"); $stmt->execute([$uid]); $stats['spent'] = $stmt->fetch()['s'] ?: 0;

require_once __DIR__ . '/../includes/header.php';
?>
<h2>Welcome, <?php echo e($_SESSION['name']); ?></h2>
<div class="grid grid-4">
    <div class="stat-card"><div class="num"><?php echo $stats['total']; ?></div><div class="label">Total Bookings</div></div>
    <div class="stat-card"><div class="num"><?php echo $stats['active']; ?></div><div class="label">Active Rentals</div></div>
    <div class="stat-card"><div class="num"><?php echo $stats['completed']; ?></div><div class="label">Completed</div></div>
    <div class="stat-card"><div class="num"><?php echo money($stats['spent']); ?></div><div class="label">Total Spent</div></div>
</div>
<div class="grid grid-3" style="margin-top:20px;">
    <a class="card" href="../vehicles/search.php"><h3>🔍 Find a Vehicle</h3><p class="muted">Search & book your next rental</p></a>
    <a class="card" href="../booking/my_bookings.php"><h3>📋 My Bookings</h3><p class="muted">View & manage your bookings</p></a>
    <a class="card" href="profile.php"><h3>👤 Profile & KYC</h3><p class="muted">Update profile, upload documents</p></a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
