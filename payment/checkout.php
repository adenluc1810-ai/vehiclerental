<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');
$bookingId = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

$stmt = $pdo->prepare("SELECT b.*, v.brand, v.model FROM bookings b JOIN vehicles v ON b.vehicle_id=v.id WHERE b.id = ? AND b.customer_id = ?");
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$b = $stmt->fetch();
if (!$b) { flash('error', 'Booking not found.'); redirect('booking/my_bookings.php'); }

$stmt = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ? AND status = 'success'");
$stmt->execute([$bookingId]);
if ($stmt->fetch()) { flash('info', 'This booking has already been paid.'); redirect('booking/my_bookings.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    if (!csrfCheck()) {
        flash('error', 'Your session expired. Please try the payment again.');
        redirect('payment/checkout.php?booking_id=' . $bookingId);
    }
    // Simulated payment gateway processing
    $allowed = ['Card','UPI','NetBanking','Cash','Wallet'];
    $method  = in_array($_POST['method'] ?? '', $allowed, true) ? $_POST['method'] : 'Card';
    $txnId = 'TXN' . strtoupper(uniqid());

    $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, method, transaction_id, status) VALUES (?,?,?,?, 'success')");
    $stmt->execute([$bookingId, $b['total_amount'], $method, $txnId]);

    $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND status = 'pending'")->execute([$bookingId]);

    notify($_SESSION['user_id'], 'Payment Successful', "Payment of " . money($b['total_amount']) . " received for booking #$bookingId. Txn: $txnId");

    flash('success', 'Payment successful! Your booking is confirmed.');
    redirect('booking/receipt.php?booking_id=' . $bookingId . '&new=1');
}

$pageTitle = 'Checkout';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:480px;margin:0 auto;">
    <h2>Checkout</h2>
    <p><strong>Vehicle:</strong> <?php echo e($b['brand'].' '.$b['model']); ?></p>
    <p><strong>Duration:</strong> <?php echo e($b['duration_days']); ?> day(s)</p>
    <p class="price">Amount to pay: <?php echo money($b['total_amount']); ?></p>
    <form method="post">
        <?php echo csrfField(); ?>
        <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">
        <div class="form-group">
            <label>Payment Method</label>
            <select name="method">
                <option>Card</option><option>UPI</option><option>NetBanking</option><option>Wallet</option><option>Cash</option>
            </select>
        </div>
        <div class="form-group">
            <label>Card / Reference Number (demo only)</label>
            <input type="text" placeholder="4242 4242 4242 4242" maxlength="19">
        </div>
        <button class="btn" type="submit" name="pay" value="1" style="width:100%;">Pay <?php echo money($b['total_amount']); ?></button>
    </form>
    <p class="muted" style="margin-top:10px;">This is a simulated payment gateway for demo purposes. No real transaction occurs.</p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
