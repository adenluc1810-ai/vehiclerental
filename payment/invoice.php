<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$bookingId = (int)($_GET['booking_id'] ?? 0);

$stmt = $pdo->prepare("SELECT b.*, v.brand, v.model, v.owner_id, c.name customer_name, c.email customer_email
    FROM bookings b JOIN vehicles v ON b.vehicle_id=v.id JOIN users c ON b.customer_id=c.id WHERE b.id = ?");
$stmt->execute([$bookingId]);
$b = $stmt->fetch();
if (!$b) { flash('error', 'Booking not found.'); redirect('index.php'); }

// permission: customer who owns it, owner of vehicle, or admin
if ($_SESSION['role'] === 'customer' && $b['customer_id'] != $_SESSION['user_id']) redirect('index.php');
if ($_SESSION['role'] === 'owner' && $b['owner_id'] != $_SESSION['user_id']) redirect('index.php');

$stmt = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$bookingId]);
$payment = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM invoices WHERE booking_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$bookingId]);
$invoice = $stmt->fetch();

$pageTitle = 'Invoice #' . $bookingId;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:650px;margin:0 auto;">
    <div class="flex-between">
        <h2>Invoice / Bill</h2>
        <span class="status-pill status-<?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span>
    </div>
    <p><strong>Booking #:</strong> <?php echo $b['id']; ?></p>
    <p><strong>Customer:</strong> <?php echo e($b['customer_name']); ?> (<?php echo e($b['customer_email']); ?>)</p>
    <p><strong>Vehicle:</strong> <?php echo e($b['brand'].' '.$b['model']); ?></p>
    <p><strong>Rental Period:</strong> <?php echo e($b['start_date']).' → '.e($b['end_date']); ?> (<?php echo $b['duration_days']; ?> days)</p>
    <p><strong>Pickup:</strong> <?php echo e($b['pickup_location']); ?> &nbsp; <strong>Drop:</strong> <?php echo e($b['drop_location']); ?></p>
    <hr>
    <table>
        <tr><td>Rental Charge (<?php echo $b['duration_days']; ?> × <?php echo money($b['rate_per_day']); ?>)</td><td><?php echo money($b['total_amount']); ?></td></tr>
        <?php if ($invoice): ?>
        <tr><td>Late Fee</td><td><?php echo money($invoice['late_fee']); ?></td></tr>
        <tr><td>Damage Charge</td><td><?php echo money($invoice['damage_charge']); ?></td></tr>
        <tr><td>Tax (5%)</td><td><?php echo money($invoice['tax']); ?></td></tr>
        <tr><th>Total</th><th><?php echo money($invoice['total']); ?></th></tr>
        <?php else: ?>
        <tr><th>Total (pending final settlement)</th><th><?php echo money($b['total_amount']); ?></th></tr>
        <?php endif; ?>
    </table>
    <hr>
    <p><strong>Payment Status:</strong>
        <?php if ($payment): ?>
            <span class="status-pill status-<?php echo $payment['status']; ?>"><?php echo ucfirst($payment['status']); ?></span>
            — <?php echo e($payment['method']); ?> — Txn: <?php echo e($payment['transaction_id']); ?>
        <?php else: ?>
            <span class="status-pill status-pending">Unpaid</span>
        <?php endif; ?>
    </p>
    <button class="btn" onclick="window.print()">Print / Save as PDF</button>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
