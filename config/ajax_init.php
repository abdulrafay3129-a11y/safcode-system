<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

include("db.php");
include("logger.php");
include("auth.php");

/* ONLY CHECK SESSION EXISTS */
if(!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])){
    echo "SESSION_EXPIRED";
    exit;
}

/* TOKEN VERIFY (lightweight) */
$stmt = $conn->prepare("SELECT session_token FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();

if(!$res || $res->num_rows == 0){
    echo "SESSION_EXPIRED";
    exit;
}

$user = $res->fetch_assoc();

if($user['session_token'] !== $_SESSION['session_token']){
    echo "SESSION_EXPIRED";
    exit;
}
?>