<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, address=? WHERE id=?");
    $stmt->execute([trim($_POST['name']), trim($_POST['phone']), trim($_POST['address']), $uid]);
    $_SESSION['name'] = trim($_POST['name']);
    flash('success', 'Profile updated.');
    redirect('customer/profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_kyc'])) {
    $file = uploadFile('kyc_file', __DIR__ . '/../uploads/kyc', ['jpg','jpeg','png','pdf']);
    if ($file) {
        $stmt = $pdo->prepare("INSERT INTO kyc_documents (user_id, doc_type, file_path) VALUES (?,?,?)");
        $stmt->execute([$uid, $_POST['doc_type'], $file]);
        flash('success', 'Document uploaded for verification.');
    } else {
        flash('error', 'Invalid file. Allowed: jpg, png, pdf.');
    }
    redirect('customer/profile.php');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM kyc_documents WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$uid]);
$docs = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT b.*, v.brand, v.model FROM bookings b JOIN vehicles v ON b.vehicle_id=v.id WHERE customer_id = ? ORDER BY b.created_at DESC LIMIT 10");
$stmt->execute([$uid]);
$history = $stmt->fetchAll();

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="grid grid-2">
    <div class="card">
        <h3>Profile</h3>
        <form method="post">
            <div class="form-group"><label>Name</label><input type="text" name="name" value="<?php echo e($user['name']); ?>" required></div>
            <div class="form-group"><label>Email</label><input type="email" value="<?php echo e($user['email']); ?>" disabled></div>
            <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?php echo e($user['phone']); ?>"></div>
            <div class="form-group"><label>Address</label><textarea name="address" rows="2"><?php echo e($user['address']); ?></textarea></div>
            <button class="btn" type="submit" name="update_profile" value="1">Save Profile</button>
        </form>
    </div>
    <div class="card">
        <h3>KYC Document Upload</h3>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Document Type</label>
                <select name="doc_type"><option>Driving License</option><option>National ID</option><option>Passport</option></select>
            </div>
            <div class="form-group"><label>File (jpg/png/pdf)</label><input type="file" name="kyc_file" required></div>
            <button class="btn" type="submit" name="upload_kyc" value="1">Upload Document</button>
        </form>
        <h4>Uploaded Documents</h4>
        <?php foreach ($docs as $d): ?>
            <p><?php echo e($d['doc_type']); ?> — <span class="status-pill status-<?php echo $d['status']; ?>"><?php echo ucfirst($d['status']); ?></span></p>
        <?php endforeach; ?>
        <?php if (!$docs): ?><p class="muted">No documents uploaded yet.</p><?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>Recent Booking History</h3>
    <table>
        <tr><th>Vehicle</th><th>Dates</th><th>Total</th><th>Status</th></tr>
        <?php foreach ($history as $h): ?>
        <tr>
            <td><?php echo e($h['brand'].' '.$h['model']); ?></td>
            <td><?php echo e($h['start_date']).' → '.e($h['end_date']); ?></td>
            <td><?php echo money($h['total_amount']); ?></td>
            <td><span class="status-pill status-<?php echo $h['status']; ?>"><?php echo ucfirst($h['status']); ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$history): ?><tr><td colspan="4" class="muted">No bookings yet.</td></tr><?php endif; ?>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
