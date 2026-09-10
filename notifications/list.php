<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$pageTitle = 'Notifications';
$uid = $_SESSION['user_id'];

// mark all as read
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$uid]);

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$uid]);
$notes = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<h2>Notifications</h2>
<div class="card">
<?php foreach ($notes as $n): ?>
    <div style="border-bottom:1px solid #eee;padding:12px 0;">
        <strong><?php echo e($n['title']); ?></strong>
        <p><?php echo e($n['message']); ?></p>
        <span class="muted"><?php echo date('M j, Y g:i A', strtotime($n['created_at'])); ?></span>
    </div>
<?php endforeach; ?>
<?php if (!$notes): ?><p class="muted">No notifications yet.</p><?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
