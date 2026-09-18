<?php
/**
 * Creates a reservation and sends the customer to a printable receipt.
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');

// Only POST may create a booking
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'Invalid request. Please start from the vehicle page.');
    redirect('vehicles/search.php');
}

if (!csrfCheck()) {
    flash('error', 'Your session expired. Please log in again and retry the reservation.');
    redirect('vehicles/search.php');
}

$vehicleId = (int)($_POST['vehicle_id'] ?? 0);
$startDate = trim($_POST['start_date'] ?? '');
$endDate   = trim($_POST['end_date'] ?? '');
$pickup    = trim($_POST['pickup_location'] ?? '');
$drop      = trim($_POST['drop_location'] ?? '');

$back = 'vehicles/view.php?id=' . $vehicleId;

// ---------- validation ----------
if ($vehicleId <= 0) {
    flash('error', 'No vehicle selected.');
    redirect('vehicles/search.php');
}
if (!isValidDate($startDate) || !isValidDate($endDate)) {
    flash('error', 'Please choose a valid pickup and drop date.');
    redirect($back);
}
if ($startDate < date('Y-m-d')) {
    flash('error', 'Pickup date cannot be in the past.');
    redirect($back);
}
if ($endDate <= $startDate) {
    flash('error', 'Drop date must be after the pickup date.');
    redirect($back);
}
if ($pickup === '' || $drop === '') {
    flash('error', 'Pickup and drop locations are required.');
    redirect($back);
}

$days = daysBetween($startDate, $endDate);
if ($days < 1) {
    flash('error', 'The rental must be at least one day long.');
    redirect($back);
}
if ($days > 365) {
    flash('error', 'Bookings are limited to 365 days.');
    redirect($back);
}

// Account must be active
$me = currentUser();
if (!$me || $me['status'] !== 'active') {
    flash('error', 'Your account is blocked. Please contact support.');
    redirect('index.php');
}

// ---------- create the booking atomically ----------
try {
    $pdo->beginTransaction();

    // Lock the vehicle row so two customers cannot grab the same dates at once
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? FOR UPDATE");
    $stmt->execute([$vehicleId]);
    $vehicle = $stmt->fetch();

    if (!$vehicle) {
        $pdo->rollBack();
        flash('error', 'That vehicle no longer exists.');
        redirect('vehicles/search.php');
    }
    if ($vehicle['status'] !== 'available') {
        $pdo->rollBack();
        flash('error', 'This vehicle is not available for booking right now.');
        redirect($back);
    }
    if ((int)$vehicle['owner_id'] === (int)$_SESSION['user_id']) {
        $pdo->rollBack();
        flash('error', 'You cannot book your own vehicle.');
        redirect($back);
    }

    // Re-check date availability inside the transaction
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) c FROM bookings
         WHERE vehicle_id = ?
           AND status IN ('pending','confirmed','ongoing')
           AND NOT (end_date <= ? OR start_date >= ?)"
    );
    $stmt->execute([$vehicleId, $startDate, $endDate]);
    if ((int)$stmt->fetch()['c'] > 0) {
        $pdo->rollBack();
        flash('error', 'Sorry — this vehicle is already booked for those dates. Please pick different dates.');
        redirect($back . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate));
    }

    // Block an accidental double-submit of the same reservation
    $stmt = $pdo->prepare(
        "SELECT id FROM bookings
         WHERE customer_id = ? AND vehicle_id = ? AND start_date = ? AND end_date = ?
           AND status IN ('pending','confirmed','ongoing') LIMIT 1"
    );
    $stmt->execute([$_SESSION['user_id'], $vehicleId, $startDate, $endDate]);
    if ($dup = $stmt->fetch()) {
        $pdo->rollBack();
        flash('info', 'You already have this reservation.');
        redirect('booking/receipt.php?booking_id=' . $dup['id']);
    }

    $rate  = (float)$vehicle['price_per_day'];
    $total = round($days * $rate, 2);

    $stmt = $pdo->prepare(
        "INSERT INTO bookings
            (customer_id, vehicle_id, start_date, end_date, pickup_location, drop_location,
             duration_days, rate_per_day, total_amount, status)
         VALUES (?,?,?,?,?,?,?,?,?, 'pending')"
    );
    $stmt->execute([
        $_SESSION['user_id'], $vehicleId, $startDate, $endDate,
        $pickup, $drop, $days, $rate, $total
    ]);
    $bookingId = (int)$pdo->lastInsertId();

    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Booking failed: ' . $ex->getMessage());
    flash('error', 'We could not complete your reservation. Please try again.');
    redirect($back);
}

// ---------- notifications ----------
$ref = bookingRef($bookingId);
notify(
    $_SESSION['user_id'],
    'Reservation Confirmed',
    "Reservation $ref for {$vehicle['brand']} {$vehicle['model']} ($startDate to $endDate) is reserved. "
    . "Amount due: " . money($total) . "."
);
notify(
    $vehicle['owner_id'],
    'New Reservation',
    "New reservation $ref for your {$vehicle['brand']} {$vehicle['model']} from $startDate to $endDate."
);

flash('success', "Reserved! Your reference number is $ref. Here is your receipt.");
redirect('booking/receipt.php?booking_id=' . $bookingId . '&new=1');
