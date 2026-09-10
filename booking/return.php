<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['owner','admin']);
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT b.*, v.owner_id, v.brand, v.model, v.price_per_day FROM bookings b JOIN vehicles v ON b.vehicle_id=v.id WHERE b.id = ?");
$stmt->execute([$id]);
$b = $stmt->fetch();
if (!$b || ($_SESSION['role'] === 'owner' && $b['owner_id'] != $_SESSION['user_id'])) {
    flash('error', 'Booking not found.');
    redirect('owner/bookings.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conditionBefore = trim($_POST['condition_before']);
    $conditionAfter = trim($_POST['condition_after']);
    $damageNotes = trim($_POST['damage_notes']);
    $damageCharge = (float)($_POST['damage_charge'] ?: 0);
    $actualReturnDate = $_POST['actual_return_date'];

    $lateDays = 0; $lateFee = 0;
    $scheduledEnd = new DateTime($b['end_date']);
    $actualEnd = new DateTime($actualReturnDate);
    if ($actualEnd > $scheduledEnd) {
        $lateDays = $scheduledEnd->diff($actualEnd)->days;
        $lateFee = $lateDays * ($b['price_per_day'] * 0.2); // 20% of daily rate per late day
    }

    $stmt = $pdo->prepare("INSERT INTO inspections (booking_id, condition_before, condition_after, damage_notes, damage_charge, late_days, late_fee, inspected_by)
        VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$id, $conditionBefore, $conditionAfter, $damageNotes, $damageCharge, $lateDays, $lateFee, $_SESSION['user_id']]);

    // Generate invoice
    $tax = round($b['total_amount'] * 0.05, 2);
    $total = $b['total_amount'] + $lateFee + $damageCharge + $tax;
    $stmt = $pdo->prepare("INSERT INTO invoices (booking_id, rental_charge, late_fee, damage_charge, tax, total) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$id, $b['total_amount'], $lateFee, $damageCharge, $tax, $total]);

    $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?")->execute([$id]);
    $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?")->execute([$b['vehicle_id']]);

    notify($b['customer_id'], 'Vehicle Returned', "Your rental of {$b['brand']} {$b['model']} is complete. Final amount: " . money($total) . ($lateFee||$damageCharge ? ' (includes late/damage fees)' : ''));

    flash('success', 'Return processed and invoice generated.');
    redirect('payment/invoice.php?booking_id=' . $id);
}

$pageTitle = 'Vehicle Return & Inspection';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:650px;margin:0 auto;">
    <h2>Return & Inspection – Booking #<?php echo $id; ?></h2>
    <p><strong>Vehicle:</strong> <?php echo e($b['brand'].' '.$b['model']); ?></p>
    <p><strong>Scheduled return:</strong> <?php echo e($b['end_date']); ?></p>
    <form method="post">
        <div class="form-group">
            <label>Actual Return Date *</label>
            <input type="date" name="actual_return_date" required value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="form-group">
            <label>Condition Before Rental</label>
            <textarea name="condition_before" rows="2" placeholder="e.g. No scratches, full fuel tank"></textarea>
        </div>
        <div class="form-group">
            <label>Condition After Rental *</label>
            <textarea name="condition_after" rows="2" required placeholder="e.g. Minor scratch on rear bumper"></textarea>
        </div>
        <div class="form-group">
            <label>Damage Notes</label>
            <textarea name="damage_notes" rows="2"></textarea>
        </div>
        <div class="form-group">
            <label>Damage Charge ($)</label>
            <input type="number" step="0.01" name="damage_charge" value="0">
        </div>
        <button class="btn" type="submit">Complete Return & Generate Invoice</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
