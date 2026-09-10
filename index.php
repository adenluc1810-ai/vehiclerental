<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Home';

// Featured vehicles
$stmt = $pdo->query("SELECT v.*, u.name AS owner_name,
    (SELECT ROUND(AVG(rating),1) FROM reviews r WHERE r.vehicle_id = v.id) AS avg_rating
    FROM vehicles v JOIN users u ON v.owner_id = u.id
    WHERE v.status = 'available' ORDER BY v.created_at DESC LIMIT 8");
$vehicles = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="hero">
    <h1>Find & Rent the Perfect Vehicle</h1>
    <p>Cars, bikes, SUVs and more — book instantly with real-time availability.</p>
    <form class="search-bar" action="vehicles/search.php" method="get">
        <input type="text" name="location" placeholder="Pickup location">
        <select name="category">
            <option value="">Any Category</option>
            <option>Car</option><option>Bike</option><option>SUV</option><option>Van</option><option>Truck</option><option>Bus</option>
        </select>
        <input type="date" name="start_date">
        <input type="date" name="end_date">
        <button type="submit" class="btn">Search Vehicles</button>
    </form>
</div>

<h2>Featured Vehicles</h2>
<div class="grid grid-4">
<?php if (!$vehicles): ?>
    <p class="muted">No vehicles listed yet. Check back soon!</p>
<?php endif; ?>
<?php foreach ($vehicles as $v): ?>
    <div class="vehicle-card">
        <img src="<?php echo $v['image'] ? BASE_URL . 'uploads/vehicles/' . e($v['image']) : 'https://via.placeholder.com/300x170?text=' . urlencode($v['brand']); ?>" alt="">
        <div class="body">
            <h3><?php echo e($v['brand'] . ' ' . $v['model']); ?></h3>
            <span class="tag"><?php echo e($v['category']); ?></span>
            <span class="tag"><?php echo e($v['location']); ?></span>
            <p class="price"><?php echo money($v['price_per_day']); ?> / day</p>
            <p class="muted">⭐ <?php echo $v['avg_rating'] ?: 'No reviews yet'; ?></p>
            <a class="btn btn-sm" href="vehicles/view.php?id=<?php echo $v['id']; ?>">View Details</a>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
