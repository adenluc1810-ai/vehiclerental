<?php
require_once __DIR__ . '/includes/functions.php';
if (isLoggedIn()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $role = in_array($_POST['role'], ['customer','owner']) ? $_POST['role'] : 'customer';

    if (!$name || !$email || !$password) {
        flash('error', 'Please fill all required fields.');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            flash('error', 'Email already registered.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hash, $phone, $role]);
            $userId = $pdo->lastInsertId();
            notify($userId, 'Welcome to VRMS', 'Your account has been created successfully.');
            flash('success', 'Registration successful. Please login.');
            redirect('login.php');
        }
    }
}
$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:480px;margin:0 auto;">
    <h2>Create an Account</h2>
    <form method="post">
        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password" required minlength="6">
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone">
        </div>
        <div class="form-group">
            <label>Register as</label>
            <select name="role">
                <option value="customer">Customer (rent vehicles)</option>
                <option value="owner">Vehicle Owner (list vehicles)</option>
            </select>
        </div>
        <button class="btn" type="submit" style="width:100%;">Register</button>
    </form>
    <p class="muted" style="margin-top:14px;">Already have an account? <a href="login.php">Login here</a></p>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
