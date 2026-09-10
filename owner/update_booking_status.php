<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['owner','admin']);

$id = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';
$allowed = ['confirmed','ongoing','completed','cancelled'];
if (!in_array($status, $allowed)) redirect('owner/bookings.php');

$stmt = $pdo->prepare("SELECT b.*, v.owner_id, v.brand, v.model FROM bookings b JOIN vehicles v ON b.vehicle_id=v.id WHERE b.id = ?");
$stmt->execute([$id]);
$b = $stmt->fetch();

if ($b && ($_SESSION['role'] === 'admin' || $b['owner_id'] == $_SESSION['user_id'])) {
    $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?")->execute([$status, $id]);
    if ($status === 'ongoing') {
        $pdo->prepare("UPDATE vehicles SET status = 'booked' WHERE id = ?")->execute([$b['vehicle_id']]);
    }
    notify($b['customer_id'], 'Booking Update', "Your booking #$id for {$b['brand']} {$b['model']} is now " . ucfirst($status) . ".");
    flash('success', 'Booking status updated.');
} else {
    flash('error', 'Not permitted.');
}
redirect('owner/bookings.php');
