<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 

/* PATH FIX */
include(__DIR__ . "/db.php");
include(__DIR__ . "/logger.php");
include(__DIR__ . "/auth.php");
include(__DIR__ . "/helpers.php");

$current = basename($_SERVER['PHP_SELF']);
if ($current == "login.php") return;

/* 1. SESSION CHECK */
if (!isset($_SESSION['user_id'], $_SESSION['session_token'])) {
    session_destroy();
    header("Location: /auth/login.php");
    exit;
}

/* 2. USER & TIMEOUT CHECK (Database se hi time calculate karenge) */
// Hum SQL mein hi check kar lenge ke kya last_activity 1800 seconds se purani hai
$stmt = $conn->prepare("SELECT session_token, 
                        (CASE WHEN last_activity < NOW() - INTERVAL 30 MINUTE THEN 1 ELSE 0 END) as is_expired 
                        FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* 3. TOKEN VALIDATION */
if (!$user || $user['session_token'] !== $_SESSION['session_token']) {
    session_destroy();
    header("Location: /auth/login.php");
    exit;
}

/* 4. AUTO LOGOUT LOGIC */
if ($user['is_expired'] == 1) {
    $up = $conn->prepare("UPDATE users SET session_token=NULL, last_activity=NULL WHERE id=?");
    $up->bind_param("i", $_SESSION['user_id']);
    $up->execute();

    session_destroy();
    header("Location: /auth/login.php?message=Session expired");
    exit;
}

/* 5. UPDATE ACTIVITY */
$up = $conn->prepare("UPDATE users SET last_activity=NOW() WHERE id=?");
$up->bind_param("i", $_SESSION['user_id']);
$up->execute();

/* CACHE CONTROL */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>