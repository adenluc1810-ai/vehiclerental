<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$pageTitle = 'Manage Users';

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if ($u) {
        $new = $u['status'] === 'active' ? 'blocked' : 'active';
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new, $id]);
        flash('success', 'User status updated.');
    }
    redirect('admin/users.php');
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<h2>Manage Users</h2>
<table>
<tr><th>Name</th><th>Email</th><th>Role</th><th>Phone</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
<?php foreach ($users as $u): ?>
<tr>
    <td><?php echo e($u['name']); ?></td>
    <td><?php echo e($u['email']); ?></td>
    <td><?php echo ucfirst($u['role']); ?></td>
    <td><?php echo e($u['phone']); ?></td>
    <td><span class="status-pill status-<?php echo $u['status']==='active'?'available':'inactive'; ?>"><?php echo ucfirst($u['status']); ?></span></td>
    <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
    <td>
        <?php if ($u['role'] !== 'admin'): ?>
        <a class="btn btn-sm <?php echo $u['status']==='active'?'btn-danger':'btn-success'; ?>" href="?toggle=<?php echo $u['id']; ?>" onclick="return confirm('Change status for this user?');">
            <?php echo $u['status']==='active' ? 'Block' : 'Unblock'; ?>
        </a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
