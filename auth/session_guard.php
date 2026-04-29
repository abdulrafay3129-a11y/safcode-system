<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include(__DIR__ . "/../config/db.php");
include(__DIR__ . "/../config/logger.php");

/* =========================
   1. BASIC LOGIN CHECK
========================= */
if (!isset($_SESSION['user_id'], $_SESSION['session_token'])) {
    session_destroy();
    header("Location: /safcode/auth/login.php");
    exit;
}

/* =========================
   2. GET USER FROM DB
========================= */
$stmt = $conn->prepare("SELECT session_token, last_activity FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();

/* USER NOT FOUND */
if (!$res || $res->num_rows == 0) {

    session_destroy();
    header("Location: /safcode/auth/login.php");
    exit;
}

$user = $res->fetch_assoc();

/* =========================
   3. STRICT TOKEN CHECK
========================= */
if (
    empty($user['session_token']) ||
    empty($_SESSION['session_token']) ||
    $user['session_token'] !== $_SESSION['session_token']
) {
    session_destroy();
    header("Location: /safcode/auth/login.php");
    exit;
}

/* =========================
   4. AUTO LOGOUT (INACTIVITY)
========================= */
$timeout = 1800; // 30 minutes (recommended)

$last = strtotime($user['last_activity'] ?? 'now');

if (time() - $last > $timeout) {

    logActivity(
        $_SESSION['user_id'],
        $_SESSION['role'],
        "Auto logout inactive"
    );

    // clear DB session
    $up = $conn->prepare("UPDATE users SET session_token=NULL, last_activity=NULL WHERE id=?");
    $up->bind_param("i", $_SESSION['user_id']);
    $up->execute();

    session_destroy();

    header("Location: /safcode/auth/login.php?message=Session expired");
    exit;
}

/* =========================
   5. PREVENT CACHING (IMPORTANT)
========================= */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>