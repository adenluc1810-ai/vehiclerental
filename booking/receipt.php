<?php
/**
 * Printable reservation receipt shown immediately after a booking is created,
 * and reachable any time from "My Bookings".
 */
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$bookingId = (int)($_GET['booking_id'] ?? 0);
$isNew     = isset($_GET['new']);

$stmt = $pdo->prepare(
    "SELECT b.*,
            v.brand, v.model, v.year, v.category, v.registration_no, v.image, v.owner_id,
            o.name AS owner_name, o.phone AS owner_phone, o.email AS owner_email,
            c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
     FROM bookings b
     JOIN vehicles v ON b.vehicle_id = v.id
     JOIN users o    ON v.owner_id   = o.id
     JOIN users c    ON b.customer_id = c.id
     WHERE b.id = ?"
);
$stmt->execute([$bookingId]);
$b = $stmt->fetch();

if (!$b) {
    flash('error', 'Reservation not found.');
    redirect('index.php');
}

// Access control: the customer who booked, the vehicle owner, or an admin
$role = $_SESSION['role'];
$uid  = (int)$_SESSION['user_id'];
if ($role === 'customer' && (int)$b['customer_id'] !== $uid) { flash('error','Not allowed.'); redirect('index.php'); }
if ($role === 'owner'    && (int)$b['owner_id']    !== $uid) { flash('error','Not allowed.'); redirect('index.php'); }

// Latest payment, if any
$stmt = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$bookingId]);
$payment = $stmt->fetch();
$isPaid  = $payment && $payment['status'] === 'success';

$ref = bookingRef($b['id']);
$pageTitle = 'Receipt ' . $ref;
require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($isNew): ?>
<div class="no-print" style="text-align:center;margin-bottom:8px;">
    <h2 style="margin:0;">🎉 Reservation Confirmed</h2>
    <p class="muted">Save or print this receipt. A copy is always available under “My Bookings”.</p>
</div>
<?php endif; ?>

<div class="card receipt" id="receipt">
    <div class="receipt-head">
        <div>
            <div class="receipt-brand">🚗 VRMS</div>
            <div class="muted">Online Vehicle Rental Management System</div>
        </div>
        <div style="text-align:right;">
            <div class="receipt-title">RESERVATION RECEIPT</div>
            <div class="receipt-ref"><?php echo e($ref); ?></div>
            <div class="muted">Issued <?php echo date('d M Y, h:i A'); ?></div>
        </div>
    </div>

    <div class="receipt-status">
        <span class="status-pill status-<?php echo e($b['status']); ?>">Booking: <?php echo ucfirst($b['status']); ?></span>
        <span class="status-pill status-<?php echo $isPaid ? 'success' : 'pending'; ?>">
            Payment: <?php echo $isPaid ? 'Paid' : 'Unpaid'; ?>
        </span>
    </div>

    <div class="grid grid-2 receipt-parties">
        <div>
            <h4>Customer</h4>
            <p>
                <?php echo e($b['customer_name']); ?><br>
                <?php echo e($b['customer_email']); ?><br>
                <?php echo e($b['customer_phone'] ?: '—'); ?>
            </p>
        </div>
        <div>
            <h4>Vehicle Owner</h4>
            <p>
                <?php echo e($b['owner_name']); ?><br>
                <?php echo e($b['owner_email']); ?><br>
                <?php echo e($b['owner_phone'] ?: '—'); ?>
            </p>
        </div>
    </div>

    <h4>Reservation Details</h4>
    <table class="receipt-table">
        <tr><td>Vehicle</td>
            <td><?php echo e(trim($b['brand'] . ' ' . $b['model'] . ' ' . ($b['year'] ? '(' . $b['year'] . ')' : ''))); ?></td></tr>
        <tr><td>Category</td><td><?php echo e($b['category']); ?></td></tr>
        <tr><td>Registration No.</td><td><?php echo e($b['registration_no'] ?: '—'); ?></td></tr>
        <tr><td>Pickup</td><td><?php echo e(date('d M Y', strtotime($b['start_date']))); ?> — <?php echo e($b['pickup_location']); ?></td></tr>
        <tr><td>Drop</td><td><?php echo e(date('d M Y', strtotime($b['end_date']))); ?> — <?php echo e($b['drop_location']); ?></td></tr>
        <tr><td>Duration</td><td><?php echo (int)$b['duration_days']; ?> day(s)</td></tr>
        <tr><td>Booked On</td><td><?php echo e(date('d M Y, h:i A', strtotime($b['created_at']))); ?></td></tr>
    </table>

    <h4>Charges</h4>
    <table class="receipt-table">
        <tr>
            <td>Rental charge (<?php echo (int)$b['duration_days']; ?> × <?php echo money($b['rate_per_day']); ?>)</td>
            <td class="amt"><?php echo money($b['total_amount']); ?></td>
        </tr>
        <tr class="total-row">
            <th>Total <?php echo $isPaid ? 'Paid' : 'Payable'; ?></th>
            <th class="amt"><?php echo money($b['total_amount']); ?></th>
        </tr>
    </table>

    <?php if ($isPaid): ?>
        <p class="paid-note">
            <strong>Payment received.</strong>
            <?php echo e($payment['method']); ?> · Transaction <?php echo e($payment['transaction_id']); ?>
            · <?php echo e(date('d M Y, h:i A', strtotime($payment['paid_at']))); ?>
        </p>
    <?php else: ?>
        <p class="muted">
            This reservation is held as <strong>pending</strong> until payment is completed.
            Final charges (late fee, damages, tax) are settled on the invoice after the vehicle is returned.
        </p>
    <?php endif; ?>

    <p class="muted receipt-foot">
        Please carry a valid driving licence and a photo ID at pickup.
        Quote reference <strong><?php echo e($ref); ?></strong> for any support request.
        This is a computer-generated receipt and needs no signature.
    </p>
</div>

<div class="flex no-print" style="justify-content:center;flex-wrap:wrap;">
    <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    <?php if ($role === 'customer' && !$isPaid && in_array($b['status'], ['pending','confirmed'], true)): ?>
        <a class="btn btn-success" href="<?php echo BASE_URL; ?>payment/checkout.php?booking_id=<?php echo (int)$b['id']; ?>">Pay <?php echo money($b['total_amount']); ?> Now</a>
    <?php endif; ?>
    <a class="btn btn-outline" href="<?php echo BASE_URL; ?>booking/my_bookings.php">My Bookings</a>
    <a class="btn btn-outline" href="<?php echo BASE_URL; ?>payment/invoice.php?booking_id=<?php echo (int)$b['id']; ?>">Full Invoice</a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
