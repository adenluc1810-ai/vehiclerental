<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('vehicles/search.php');

$vehicleId = (int)$_POST['vehicle_id'];
$startDate = $_POST['start_date'];
$endDate = $_POST['end_date'];
$pickup = trim($_POST['pickup_location']);
$drop = trim($_POST['drop_location']);

$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle || $vehicle['status'] !== 'available') {
    flash('error', 'This vehicle is not available for booking.');
    redirect('vehicles/view.php?id=' . $vehicleId);
}

$start = new DateTime($startDate);
$end = new DateTime($endDate);
$days = max(1, $start->diff($end)->days);

if ($end <= $start) {
    flash('error', 'Drop date must be after pickup date.');
    redirect('vehicles/view.php?id=' . $vehicleId);
}

if (!isVehicleAvailable($pdo, $vehicleId, $startDate, $endDate)) {
    flash('error', 'Vehicle is already booked for the selected dates.');
    redirect('vehicles/view.php?id=' . $vehicleId);
}

$total = $days * $vehicle['price_per_day'];

$stmt = $pdo->prepare("INSERT INTO bookings (customer_id, vehicle_id, start_date, end_date, pickup_location, drop_location, duration_days, rate_per_day, total_amount, status)
    VALUES (?,?,?,?,?,?,?,?,?, 'pending')");
$stmt->execute([$_SESSION['user_id'], $vehicleId, $startDate, $endDate, $pickup, $drop, $days, $vehicle['price_per_day'], $total]);
$bookingId = $pdo->lastInsertId();

notify($_SESSION['user_id'], 'Booking Created', "Your booking #$bookingId for {$vehicle['brand']} {$vehicle['model']} is pending payment.");
notify($vehicle['owner_id'], 'New Booking Request', "A new booking request (#$bookingId) was made for your {$vehicle['brand']} {$vehicle['model']}.");

redirect('payment/checkout.php?booking_id=' . $bookingId);
