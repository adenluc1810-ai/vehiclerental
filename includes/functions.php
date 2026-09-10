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
    header('Location: ' . BASE_URL . $path);
    exit;
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
            AND NOT (end_date < ? OR start_date > ?)";
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
