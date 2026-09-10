<?php
/**
 * ONE-TIME SETUP SCRIPT
 * Run this once in your browser (e.g. http://localhost/vrms/setup_admin.php)
 * to create the first admin account, then DELETE this file for security.
 */
require_once __DIR__ . '/config/db.php';

$message = '';
$stmt = $pdo->query("SELECT COUNT(*) c FROM users WHERE role = 'admin'");
$adminExists = $stmt->fetch()['c'] > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$adminExists) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($name && $email && strlen($password) >= 6) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$name, $email, $hash]);
        $message = "Admin account created successfully! You can now log in at login.php. Please delete setup_admin.php now.";
        $adminExists = true;
    } else {
        $message = "Please fill all fields (password min 6 characters).";
    }
}
?>
<!DOCTYPE html>
<html><head><title>Admin Setup</title>
<link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<div class="container" style="max-width:480px;">
    <div class="card">
        <h2>Admin Account Setup</h2>
        <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($adminExists): ?>
            <p>An admin account already exists. <a href="login.php">Go to Login</a></p>
            <p style="color:red;font-weight:bold;">For security, please delete this file (setup_admin.php) from the server now.</p>
        <?php else: ?>
            <form method="post">
                <div class="form-group"><label>Admin Name</label><input type="text" name="name" required></div>
                <div class="form-group"><label>Admin Email</label><input type="email" name="email" required></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" required minlength="6"></div>
                <button class="btn" type="submit" style="width:100%;">Create Admin Account</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body></html>
