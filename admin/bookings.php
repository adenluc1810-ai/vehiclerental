<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$pageTitle = 'All Bookings';

$stmt = $pdo->query("SELECT b.*, v.brand, v.model, c.name customer_name, o.name owner_name, p.status pay_status
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN users c ON b.customer_id = c.id
    JOIN users o ON v.owner_id = o.id
    LEFT JOIN payments p ON p.booking_id = b.id
    ORDER BY b.created_at DESC");
$bookings = $stmt->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<h2>All Bookings (Admin)</h2>
<table>
<tr><th>#</th><th>Vehicle</th><th>Customer</th><th>Owner</th><th>Dates</th><th>Total</th><th>Payment</th><th>Status</th><th>Invoice</th></tr>
<?php foreach ($bookings as $b): ?>
<tr>
    <td><?php echo $b['id']; ?></td>
    <td><?php echo e($b['brand'].' '.$b['model']); ?></td>
    <td><?php echo e($b['customer_name']); ?></td>
    <td><?php echo e($b['owner_name']); ?></td>
    <td><?php echo e($b['start_date']).' → '.e($b['end_date']); ?></td>
    <td><?php echo money($b['total_amount']); ?></td>
    <td><span class="status-pill status-<?php echo $b['pay_status'] ?: 'pending'; ?>"><?php echo ucfirst($b['pay_status'] ?: 'unpaid'); ?></span></td>
    <td><span class="status-pill status-<?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span></td>
    <td><a class="btn btn-sm btn-outline" href="../payment/invoice.php?booking_id=<?php echo $b['id']; ?>">View</a></td>
</tr>
<?php endforeach; ?>
<?php if (!$bookings): ?><tr><td colspan="9" class="muted">No bookings yet.</td></tr><?php endif; ?>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
