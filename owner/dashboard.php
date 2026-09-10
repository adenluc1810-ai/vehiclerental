<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['owner','admin']);
$pageTitle = 'Owner Dashboard';
$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM vehicles WHERE owner_id = ?"); $stmt->execute([$uid]); $totalVehicles = $stmt->fetch()['c'];
$stmt = $pdo->prepare("SELECT COUNT(*) c FROM vehicles WHERE owner_id = ? AND status='available'"); $stmt->execute([$uid]); $availableVehicles = $stmt->fetch()['c'];
$stmt = $pdo->prepare("SELECT COUNT(*) c FROM bookings b JOIN vehicles v ON b.vehicle_id=v.id WHERE v.owner_id=?"); $stmt->execute([$uid]); $totalBookings = $stmt->fetch()['c'];
$stmt = $pdo->prepare("SELECT SUM(b.total_amount) s FROM bookings b JOIN vehicles v ON b.vehicle_id=v.id WHERE v.owner_id=? AND b.status='completed'"); $stmt->execute([$uid]); $revenue = $stmt->fetch()['s'] ?: 0;

require_once __DIR__ . '/../includes/header.php';
?>
<h2>Owner Dashboard</h2>
<div class="grid grid-4">
    <div class="stat-card"><div class="num"><?php echo $totalVehicles; ?></div><div class="label">Total Vehicles</div></div>
    <div class="stat-card"><div class="num"><?php echo $availableVehicles; ?></div><div class="label">Available Now</div></div>
    <div class="stat-card"><div class="num"><?php echo $totalBookings; ?></div><div class="label">Total Bookings</div></div>
    <div class="stat-card"><div class="num"><?php echo money($revenue); ?></div><div class="label">Revenue (completed)</div></div>
</div>
<div class="grid grid-3" style="margin-top:20px;">
    <a class="card" href="../vehicles/manage.php"><h3>🚙 My Vehicles</h3><p class="muted">Add, edit or remove listings</p></a>
    <a class="card" href="bookings.php"><h3>📑 Manage Bookings</h3><p class="muted">Confirm, start rentals, process returns</p></a>
    <a class="card" href="../vehicles/add.php"><h3>➕ List New Vehicle</h3><p class="muted">Add a new vehicle to rent out</p></a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
