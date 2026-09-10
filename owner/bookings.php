<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['owner','admin']);
$pageTitle = 'Manage Bookings';

$ownerId = $_SESSION['user_id'];
if ($_SESSION['role'] === 'admin') {
    $stmt = $pdo->query("SELECT b.*, v.brand, v.model, c.name AS customer_name, p.status pay_status
        FROM bookings b JOIN vehicles v ON b.vehicle_id=v.id JOIN users c ON b.customer_id=c.id
        LEFT JOIN payments p ON p.booking_id=b.id ORDER BY b.created_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT b.*, v.brand, v.model, c.name AS customer_name, p.status pay_status
        FROM bookings b JOIN vehicles v ON b.vehicle_id=v.id JOIN users c ON b.customer_id=c.id
        LEFT JOIN payments p ON p.booking_id=b.id
        WHERE v.owner_id = ? ORDER BY b.created_at DESC");
    $stmt->execute([$ownerId]);
}
$bookings = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<h2>Manage Bookings</h2>
<table>
<tr><th>#</th><th>Vehicle</th><th>Customer</th><th>Dates</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr>
<?php foreach ($bookings as $b): ?>
<tr>
    <td><?php echo $b['id']; ?></td>
    <td><?php echo e($b['brand'].' '.$b['model']); ?></td>
    <td><?php echo e($b['customer_name']); ?></td>
    <td><?php echo e($b['start_date']).' → '.e($b['end_date']); ?></td>
    <td><?php echo money($b['total_amount']); ?></td>
    <td><span class="status-pill status-<?php echo $b['pay_status'] ?: 'pending'; ?>"><?php echo ucfirst($b['pay_status'] ?: 'unpaid'); ?></span></td>
    <td><span class="status-pill status-<?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span></td>
    <td class="flex">
        <?php if ($b['status'] === 'pending' && $b['pay_status'] === 'success'): ?>
            <a class="btn btn-sm btn-success" href="update_booking_status.php?id=<?php echo $b['id']; ?>&status=confirmed">Confirm</a>
        <?php endif; ?>
        <?php if ($b['status'] === 'confirmed'): ?>
            <a class="btn btn-sm" href="update_booking_status.php?id=<?php echo $b['id']; ?>&status=ongoing">Start Rental</a>
        <?php endif; ?>
        <?php if ($b['status'] === 'ongoing'): ?>
            <a class="btn btn-sm btn-warn" href="../booking/return.php?id=<?php echo $b['id']; ?>">Process Return</a>
        <?php endif; ?>
        <a class="btn btn-sm btn-outline" href="../payment/invoice.php?booking_id=<?php echo $b['id']; ?>">Invoice</a>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$bookings): ?>
<tr><td colspan="8" class="muted">No bookings found.</td></tr>
<?php endif; ?>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
