<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id'], $_SESSION['session_token'])) {
    header("Location: auth/login.php");
    exit;
}

/* VERIFY DB SESSION */
$stmt = $conn->prepare("SELECT session_token FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res || $res['session_token'] !== $_SESSION['session_token']) {
    session_destroy();
    header("Location: auth/login.php");
    exit;
}

/* ROLE ROUTE */
switch($_SESSION['role']) {
    case 'admin': header("Location: admin/dashboard.php"); break;
    case 'teacher': header("Location: teacher/dashboard.php"); break;
    case 'student': header("Location: student/dashboard.php"); break;
    case 'receptionist': header("Location: receptionist/dashboard.php"); break;
    default: header("Location: auth/login.php");
}
exit;
?>