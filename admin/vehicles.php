<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$pageTitle = 'Manage Vehicles';

$stmt = $pdo->query("SELECT v.*, u.name owner_name FROM vehicles v JOIN users u ON v.owner_id=u.id ORDER BY v.created_at DESC");
$vehicles = $stmt->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<h2>All Vehicles (Admin)</h2>
<table>
<tr><th>Vehicle</th><th>Owner</th><th>Category</th><th>Location</th><th>Price/day</th><th>Status</th><th>Actions</th></tr>
<?php foreach ($vehicles as $v): ?>
<tr>
    <td><?php echo e($v['brand'].' '.$v['model']); ?></td>
    <td><?php echo e($v['owner_name']); ?></td>
    <td><?php echo e($v['category']); ?></td>
    <td><?php echo e($v['location']); ?></td>
    <td><?php echo money($v['price_per_day']); ?></td>
    <td><span class="status-pill status-<?php echo $v['status']; ?>"><?php echo ucfirst($v['status']); ?></span></td>
    <td class="flex">
        <a class="btn btn-sm btn-outline" href="../vehicles/edit.php?id=<?php echo $v['id']; ?>">Edit</a>
        <a class="btn btn-sm btn-danger" href="../vehicles/delete.php?id=<?php echo $v['id']; ?>" onclick="return confirm('Delete?');">Delete</a>
        <a class="btn btn-sm" href="../vehicles/view.php?id=<?php echo $v['id']; ?>">View</a>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$vehicles): ?><tr><td colspan="7" class="muted">No vehicles listed.</td></tr><?php endif; ?>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
