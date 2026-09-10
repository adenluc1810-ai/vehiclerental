<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['owner','admin']);
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$id]);
$v = $stmt->fetch();
if ($v && ($_SESSION['role'] === 'admin' || $v['owner_id'] == $_SESSION['user_id'])) {
    $pdo->prepare("DELETE FROM vehicles WHERE id = ?")->execute([$id]);
    flash('success', 'Vehicle deleted.');
} else {
    flash('error', 'Vehicle not found or not permitted.');
}
redirect('vehicles/manage.php');
