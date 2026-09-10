<?php
require_once __DIR__ . '/functions.php';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? e($pageTitle) . ' - VRMS' : 'Online Vehicle Rental Management System'; ?></title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
<header class="navbar">
    <div class="nav-container">
        <a href="<?php echo BASE_URL; ?>index.php" class="brand">🚗 VRMS</a>
        <nav class="nav-links">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <a href="<?php echo BASE_URL; ?>vehicles/search.php">Search Vehicles</a>
            <?php if ($user): ?>
                <?php if ($user['role'] === 'customer'): ?>
                    <a href="<?php echo BASE_URL; ?>customer/dashboard.php">My Dashboard</a>
                    <a href="<?php echo BASE_URL; ?>booking/my_bookings.php">My Bookings</a>
                <?php elseif ($user['role'] === 'owner'): ?>
                    <a href="<?php echo BASE_URL; ?>owner/dashboard.php">Owner Dashboard</a>
                    <a href="<?php echo BASE_URL; ?>vehicles/manage.php">My Vehicles</a>
                <?php elseif ($user['role'] === 'admin'): ?>
                    <a href="<?php echo BASE_URL; ?>admin/dashboard.php">Admin Dashboard</a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>notifications/list.php" class="notif-link">
                    Notifications
                    <?php $unread = getUnreadCount($user['id']); if ($unread > 0): ?>
                        <span class="badge"><?php echo $unread; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo BASE_URL; ?>auth/logout.php">Logout (<?php echo e($user['name']); ?>)</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>login.php">Login</a>
                <a href="<?php echo BASE_URL; ?>register.php" class="btn-nav">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
<?php
foreach (['success','error','info'] as $ftype):
    $msg = flash($ftype);
    if ($msg):
?>
    <div class="alert alert-<?php echo $ftype; ?>"><?php echo e($msg); ?></div>
<?php endif; endforeach; ?>
