<?php
ignore_user_abort(true);
session_start();

include("../config/db.php");
include("../config/logger.php");

if (!isset($_SESSION['user_id'])) {
    exit;
}

/* LOG ONLY ONCE */
logActivity(
    $_SESSION['user_id'],
    $_SESSION['role'],
    "Logout (tab closed)"
);

/* CLEAR DB SESSION */
$stmt = $conn->prepare("UPDATE users SET session_token=NULL, last_activity=NULL WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();

/* DESTROY PHP SESSION */
$_SESSION = [];

session_unset();
session_destroy();

/* DELETE COOKIE (IMPORTANT FIX) */
setcookie(session_name(), '', time() - 3600, '/');

exit;
?>