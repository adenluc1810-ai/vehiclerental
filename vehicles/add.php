<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['owner','admin']);
$pageTitle = 'Add Vehicle';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image = uploadFile('image', __DIR__ . '/../uploads/vehicles', ['jpg','jpeg','png','webp']);
    if ($image === false) { flash('error', 'Invalid image file.'); redirect('vehicles/add.php'); }

    $stmt = $pdo->prepare("INSERT INTO vehicles
        (owner_id, category, brand, model, year, registration_no, price_per_day, location, seats, transmission, fuel_type, description, image)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $_SESSION['user_id'], $_POST['category'], trim($_POST['brand']), trim($_POST['model']),
        $_POST['year'], trim($_POST['registration_no']), $_POST['price_per_day'], trim($_POST['location']),
        $_POST['seats'], $_POST['transmission'], $_POST['fuel_type'], trim($_POST['description']), $image ?: null
    ]);
    $vehicleId = $pdo->lastInsertId();

    // Multiple gallery images
    if (!empty($_FILES['gallery']['name'][0])) {
        foreach ($_FILES['gallery']['tmp_name'] as $i => $tmp) {
            if ($_FILES['gallery']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['gallery']['name'][$i], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                    $newName = uniqid('g_', true) . '.' . $ext;
                    move_uploaded_file($tmp, __DIR__ . '/../uploads/vehicles/' . $newName);
                    $pdo->prepare("INSERT INTO vehicle_images (vehicle_id, image_path) VALUES (?, ?)")->execute([$vehicleId, $newName]);
                }
            }
        }
    }

    flash('success', 'Vehicle listed successfully.');
    redirect('vehicles/manage.php');
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:700px;margin:0 auto;">
    <h2>List a New Vehicle</h2>
    <form method="post" enctype="multipart/form-data">
        <div class="grid grid-2">
            <div class="form-group"><label>Category *</label>
                <select name="category" required>
                    <?php foreach (['Car','Bike','SUV','Van','Truck','Bus'] as $c): ?>
                    <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Brand *</label><input type="text" name="brand" required></div>
            <div class="form-group"><label>Model *</label><input type="text" name="model" required></div>
            <div class="form-group"><label>Year</label><input type="number" name="year" value="<?php echo date('Y'); ?>"></div>
            <div class="form-group"><label>Registration No.</label><input type="text" name="registration_no"></div>
            <div class="form-group"><label>Price per day ($) *</label><input type="number" step="0.01" name="price_per_day" required></div>
            <div class="form-group"><label>Location *</label><input type="text" name="location" required></div>
            <div class="form-group"><label>Seats</label><input type="number" name="seats" value="4"></div>
            <div class="form-group"><label>Transmission</label>
                <select name="transmission"><option>Manual</option><option>Automatic</option></select>
            </div>
            <div class="form-group"><label>Fuel Type</label>
                <select name="fuel_type"><option>Petrol</option><option>Diesel</option><option>Electric</option><option>Hybrid</option></select>
            </div>
        </div>
        <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
        <div class="form-group"><label>Main Image</label><input type="file" name="image" accept="image/*"></div>
        <div class="form-group"><label>Gallery Images (optional, multiple)</label><input type="file" name="gallery[]" accept="image/*" multiple></div>
        <button class="btn" type="submit">Save Vehicle</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
