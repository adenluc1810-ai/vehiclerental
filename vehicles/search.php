<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Search Vehicles';

$location = trim($_GET['location'] ?? '');
$category = trim($_GET['category'] ?? '');
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$sql = "SELECT v.*, u.name AS owner_name,
        (SELECT ROUND(AVG(rating),1) FROM reviews r WHERE r.vehicle_id = v.id) AS avg_rating
        FROM vehicles v JOIN users u ON v.owner_id = u.id
        WHERE v.status = 'available'";
$params = [];

if ($location !== '') { $sql .= " AND v.location LIKE ?"; $params[] = "%$location%"; }
if ($category !== '') { $sql .= " AND v.category = ?"; $params[] = $category; }
if ($minPrice !== '') { $sql .= " AND v.price_per_day >= ?"; $params[] = $minPrice; }
if ($maxPrice !== '') { $sql .= " AND v.price_per_day <= ?"; $params[] = $maxPrice; }

$sql .= " ORDER BY v.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

// If dates provided, filter out vehicles with overlapping bookings
if ($startDate && $endDate) {
    $vehicles = array_filter($vehicles, function($v) use ($pdo, $startDate, $endDate) {
        return isVehicleAvailable($pdo, $v['id'], $startDate, $endDate);
    });
}

require_once __DIR__ . '/../includes/header.php';
?>
<h2>Search & Filter Vehicles</h2>
<div class="card">
    <form method="get" class="grid grid-4" style="align-items:end;">
        <div class="form-group">
            <label>Location</label>
            <input type="text" name="location" value="<?php echo e($location); ?>">
        </div>
        <div class="form-group">
            <label>Category</label>
            <select name="category">
                <option value="">Any</option>
                <?php foreach (['Car','Bike','SUV','Van','Truck','Bus'] as $c): ?>
                    <option value="<?php echo $c; ?>" <?php echo $category===$c?'selected':''; ?>><?php echo $c; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Min Price/day</label>
            <input type="number" name="min_price" value="<?php echo e($minPrice); ?>">
        </div>
        <div class="form-group">
            <label>Max Price/day</label>
            <input type="number" name="max_price" value="<?php echo e($maxPrice); ?>">
        </div>
        <div class="form-group">
            <label>Pickup Date</label>
            <input type="date" name="start_date" value="<?php echo e($startDate); ?>">
        </div>
        <div class="form-group">
            <label>Drop Date</label>
            <input type="date" name="end_date" value="<?php echo e($endDate); ?>">
        </div>
        <div class="form-group">
            <button class="btn" type="submit">Filter</button>
        </div>
    </form>
</div>

<div class="grid grid-4">
<?php if (!$vehicles): ?>
    <p class="muted">No vehicles match your search criteria.</p>
<?php endif; ?>
<?php foreach ($vehicles as $v): ?>
    <div class="vehicle-card">
        <img src="<?php echo e(vehicleImageUrl($v['image'], $v['brand'] . ' ' . $v['model'])); ?>" alt="">
        <div class="body">
            <h3><?php echo e($v['brand'] . ' ' . $v['model']); ?></h3>
            <span class="tag"><?php echo e($v['category']); ?></span>
            <span class="tag"><?php echo e($v['location']); ?></span>
            <p class="price"><?php echo money($v['price_per_day']); ?> / day</p>
            <p class="muted">⭐ <?php echo $v['avg_rating'] ?: 'No reviews yet'; ?></p>
            <a class="btn btn-sm" href="view.php?id=<?php echo $v['id']; ?>&start_date=<?php echo e($startDate); ?>&end_date=<?php echo e($endDate); ?>">View Details</a>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
