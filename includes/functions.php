<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ---------- AUTH HELPERS ----------
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    return $user;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['role'], $roles)) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

// ---------- UTIL ----------
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    // Accept 'booking/receipt.php?x=1' or a full URL
    if (preg_match('#^https?://#i', $path)) {
        header('Location: ' . $path);
        exit;
    }
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

// ---------- CSRF ----------
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function csrfCheck() {
    $sent = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}

// ---------- DATES ----------
/** Returns true only for a real calendar date in YYYY-MM-DD form. */
function isValidDate($date) {
    if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
    [$y, $m, $d] = array_map('intval', explode('-', $date));
    return checkdate($m, $d, $y);
}

/** Whole days between two YYYY-MM-DD dates. */
function daysBetween($start, $end) {
    $s = new DateTime($start . ' 00:00:00');
    $e = new DateTime($end . ' 00:00:00');
    return (int)$s->diff($e)->days;
}

// ---------- BOOKING REFERENCE ----------
function bookingRef($bookingId) {
    return 'VR-' . str_pad((string)$bookingId, 6, '0', STR_PAD_LEFT);
}

// ---------- IMAGES ----------
/** Inline SVG placeholder (no external service needed). */
function placeholderImage($text, $w = 300, $h = 170) {
    $label = e(mb_substr((string)$text, 0, 22));
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . (int)$w . '" height="' . (int)$h . '">'
         . '<rect width="100%" height="100%" fill="#dfe5f1"/>'
         . '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" '
         . 'font-family="Arial,Helvetica,sans-serif" font-size="18" fill="#5b6b8c">' . $label . '</text></svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function vehicleImageUrl($imageFile, $fallbackText, $w = 300, $h = 170) {
    return $imageFile
        ? BASE_URL . 'uploads/vehicles/' . rawurlencode($imageFile)
        : placeholderImage($fallbackText, $w, $h);
}

function flash($key, $msg = null) {
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return;
    }
    if (isset($_SESSION['flash'][$key])) {
        $val = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $val;
    }
    return null;
}

function uploadFile($fileInputName, $destDir, $allowed = ['jpg','jpeg','png','pdf']) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $file = $_FILES[$fileInputName];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;
    $newName = uniqid('f_', true) . '.' . $ext;
    $destPath = rtrim($destDir, '/') . '/' . $newName;
    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        return $newName;
    }
    return false;
}

// ---------- NOTIFICATIONS ----------
function notify($userId, $title, $message) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $title, $message]);
}

function getUnreadCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetch()['c'];
}

// ---------- BOOKING / AVAILABILITY ----------
function isVehicleAvailable($pdo, $vehicleId, $startDate, $endDate, $excludeBookingId = null) {
    $sql = "SELECT COUNT(*) c FROM bookings
            WHERE vehicle_id = ?
            AND status IN ('pending','confirmed','ongoing')
            AND NOT (end_date <= ? OR start_date >= ?)";
    $params = [$vehicleId, $startDate, $endDate];
    if ($excludeBookingId) {
        $sql .= " AND id != ?";
        $params[] = $excludeBookingId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch()['c'] == 0;
}

function money($n) {
    return '$' . number_format((float)$n, 2);
}
