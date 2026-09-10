<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND customer_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$b = $stmt->fetch();

if ($b && in_array($b['status'], ['pending','confirmed'])) {
    $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$id]);
    $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?")->execute([$b['vehicle_id']]);
    notify($_SESSION['user_id'], 'Booking Cancelled', "Booking #$id has been cancelled.");
    flash('success', 'Booking cancelled.');
} else {
    flash('error', 'Unable to cancel this booking.');
}
redirect('booking/my_bookings.php');
