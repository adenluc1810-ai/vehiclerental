<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['owner','admin']);
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$id]);
$v = $stmt->fetch();
if (!$v) { flash('error', 'Vehicle not found.'); redirect('vehicles/manage.php'); }
if ($_SESSION['role'] === 'owner' && $v['owner_id'] != $_SESSION['user_id']) { redirect('vehicles/manage.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image = $v['image'];
    $newImage = uploadFile('image', __DIR__ . '/../uploads/vehicles', ['jpg','jpeg','png','webp']);
    if ($newImage) $image = $newImage;

    $stmt = $pdo->prepare("UPDATE vehicles SET category=?, brand=?, model=?, year=?, registration_no=?, price_per_day=?, location=?, seats=?, transmission=?, fuel_type=?, description=?, status=?, image=? WHERE id=?");
    $stmt->execute([
        $_POST['category'], trim($_POST['brand']), trim($_POST['model']), $_POST['year'],
        trim($_POST['registration_no']), $_POST['price_per_day'], trim($_POST['location']),
        $_POST['seats'], $_POST['transmission'], $_POST['fuel_type'], trim($_POST['description']),
        $_POST['status'], $image, $id
    ]);
    flash('success', 'Vehicle updated.');
    redirect('vehicles/manage.php');
}
$pageTitle = 'Edit Vehicle';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:700px;margin:0 auto;">
    <h2>Edit Vehicle</h2>
    <form method="post" enctype="multipart/form-data">
        <div class="grid grid-2">
            <div class="form-group"><label>Category</label>
                <select name="category">
                    <?php foreach (['Car','Bike','SUV','Van','Truck','Bus'] as $c): ?>
                    <option value="<?php echo $c; ?>" <?php echo $v['category']===$c?'selected':''; ?>><?php echo $c; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Brand</label><input type="text" name="brand" value="<?php echo e($v['brand']); ?>" required></div>
            <div class="form-group"><label>Model</label><input type="text" name="model" value="<?php echo e($v['model']); ?>" required></div>
            <div class="form-group"><label>Year</label><input type="number" name="year" value="<?php echo e($v['year']); ?>"></div>
            <div class="form-group"><label>Registration No.</label><input type="text" name="registration_no" value="<?php echo e($v['registration_no']); ?>"></div>
            <div class="form-group"><label>Price per day ($)</label><input type="number" step="0.01" name="price_per_day" value="<?php echo e($v['price_per_day']); ?>" required></div>
            <div class="form-group"><label>Location</label><input type="text" name="location" value="<?php echo e($v['location']); ?>" required></div>
            <div class="form-group"><label>Seats</label><input type="number" name="seats" value="<?php echo e($v['seats']); ?>"></div>
            <div class="form-group"><label>Transmission</label>
                <select name="transmission"><option <?php echo $v['transmission']=='Manual'?'selected':''; ?>>Manual</option><option <?php echo $v['transmission']=='Automatic'?'selected':''; ?>>Automatic</option></select>
            </div>
            <div class="form-group"><label>Fuel Type</label>
                <select name="fuel_type">
                <?php foreach (['Petrol','Diesel','Electric','Hybrid'] as $f): ?>
                    <option <?php echo $v['fuel_type']==$f?'selected':''; ?>><?php echo $f; ?></option>
                <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Status</label>
                <select name="status">
                <?php foreach (['available','booked','maintenance','inactive'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo $v['status']==$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label>Description</label><textarea name="description" rows="3"><?php echo e($v['description']); ?></textarea></div>
        <div class="form-group"><label>Replace Main Image</label><input type="file" name="image" accept="image/*"></div>
        <button class="btn" type="submit">Update Vehicle</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
