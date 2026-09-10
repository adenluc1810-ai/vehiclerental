<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['owner','admin']);
$pageTitle = 'My Vehicles';

$ownerId = $_SESSION['user_id'];
if ($_SESSION['role'] === 'admin') {
    $stmt = $pdo->query("SELECT v.*, u.name owner_name FROM vehicles v JOIN users u ON v.owner_id=u.id ORDER BY v.created_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT v.*, u.name owner_name FROM vehicles v JOIN users u ON v.owner_id=u.id WHERE owner_id = ? ORDER BY v.created_at DESC");
    $stmt->execute([$ownerId]);
}
$vehicles = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex-between">
    <h2>My Vehicles</h2>
    <a class="btn" href="add.php">+ Add New Vehicle</a>
</div>
<table>
    <tr><th>Vehicle</th><th>Category</th><th>Location</th><th>Price/day</th><th>Status</th><th>Actions</th></tr>
    <?php foreach ($vehicles as $v): ?>
    <tr>
        <td><?php echo e($v['brand'] . ' ' . $v['model']); ?> (<?php echo e($v['year']); ?>)</td>
        <td><?php echo e($v['category']); ?></td>
        <td><?php echo e($v['location']); ?></td>
        <td><?php echo money($v['price_per_day']); ?></td>
        <td><span class="status-pill status-<?php echo $v['status']; ?>"><?php echo ucfirst($v['status']); ?></span></td>
        <td class="flex">
            <a class="btn btn-sm btn-outline" href="edit.php?id=<?php echo $v['id']; ?>">Edit</a>
            <a class="btn btn-sm btn-danger" href="delete.php?id=<?php echo $v['id']; ?>" onclick="return confirm('Delete this vehicle?');">Delete</a>
            <a class="btn btn-sm" href="../vehicles/view.php?id=<?php echo $v['id']; ?>">View</a>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$vehicles): ?>
    <tr><td colspan="6" class="muted">No vehicles listed yet.</td></tr>
    <?php endif; ?>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
