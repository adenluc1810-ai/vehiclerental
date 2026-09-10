<?php
require_once __DIR__ . '/includes/functions.php';
if (isLoggedIn()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'blocked') {
            flash('error', 'Your account has been blocked. Contact admin.');
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            flash('success', 'Welcome back, ' . $user['name'] . '!');
            if ($user['role'] === 'admin') redirect('admin/dashboard.php');
            elseif ($user['role'] === 'owner') redirect('owner/dashboard.php');
            else redirect('customer/dashboard.php');
        }
    } else {
        flash('error', 'Invalid email or password.');
    }
}
$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:420px;margin:0 auto;">
    <h2>Login</h2>
    <form method="post">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button class="btn" type="submit" style="width:100%;">Login</button>
    </form>
    <p class="muted" style="margin-top:14px;">No account? <a href="register.php">Register here</a></p>
    <p class="muted">First time setup? Run <a href="setup_admin.php">setup_admin.php</a> once to create the admin account.</p>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
