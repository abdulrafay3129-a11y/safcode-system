<?php
session_start();
include("../config/db.php");

if(isset($_SESSION['user_id'])){

    $stmt = $conn->prepare("UPDATE users SET last_activity=NOW() WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();

}
?>