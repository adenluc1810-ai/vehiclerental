<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');
$pageTitle = 'My Bookings';

// One row per booking: take the latest payment only (a plain LEFT JOIN duplicated
// a booking once per payment attempt).
$stmt = $pdo->prepare("SELECT b.*, v.brand, v.model, v.image,
        (SELECT p.status FROM payments p
          WHERE p.booking_id = b.id ORDER BY (p.status='success') DESC, p.id DESC
          LIMIT 1) AS pay_status
    FROM bookings b JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.customer_id = ? ORDER BY b.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<h2>My Bookings</h2>
<table>
    <tr><th>Ref</th><th>Vehicle</th><th>Dates</th><th>Days</th><th>Total</th><th>Status</th><th>Payment</th><th>Actions</th></tr>
    <?php foreach ($bookings as $b): ?>
    <tr>
        <td><strong><?php echo e(bookingRef($b['id'])); ?></strong></td>
        <td><?php echo e($b['brand'] . ' ' . $b['model']); ?></td>
        <td><?php echo e($b['start_date']); ?> → <?php echo e($b['end_date']); ?></td>
        <td><?php echo e($b['duration_days']); ?></td>
        <td><?php echo money($b['total_amount']); ?></td>
        <td><span class="status-pill status-<?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span></td>
        <td><span class="status-pill status-<?php echo $b['pay_status'] ?: 'pending'; ?>"><?php echo ucfirst($b['pay_status'] ?: 'unpaid'); ?></span></td>
        <td class="flex">
            <?php if (!$b['pay_status'] || $b['pay_status'] !== 'success'): ?>
                <a class="btn btn-sm" href="<?php echo BASE_URL; ?>payment/checkout.php?booking_id=<?php echo $b['id']; ?>">Pay</a>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline" href="<?php echo BASE_URL; ?>booking/receipt.php?booking_id=<?php echo $b['id']; ?>">Receipt</a>
            <a class="btn btn-sm btn-outline" href="<?php echo BASE_URL; ?>payment/invoice.php?booking_id=<?php echo $b['id']; ?>">Invoice</a>
            <?php if (in_array($b['status'], ['pending','confirmed'])): ?>
                <a class="btn btn-sm btn-danger" href="cancel.php?id=<?php echo $b['id']; ?>" onclick="return confirm('Cancel this booking?');">Cancel</a>
            <?php endif; ?>
            <?php if ($b['status'] === 'completed'): ?>
                <a class="btn btn-sm btn-success" href="<?php echo BASE_URL; ?>review/add.php?booking_id=<?php echo $b['id']; ?>">Leave Review</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$bookings): ?>
    <tr><td colspan="8" class="muted">You have no bookings yet. <a href="../vehicles/search.php">Browse vehicles</a></td></tr>
    <?php endif; ?>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
