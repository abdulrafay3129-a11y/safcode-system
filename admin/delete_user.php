<?php
include("../config/init.php");
checkRole(['admin']);

$id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT name, role FROM users WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows > 0){
    $user = $res->fetch_assoc();

    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();

    logActivity( $_SESSION['user_id'], $_SESSION['role'], 
    "Deleted user: ".$user['name']." (".$user['role'].")");
}

header("Location: manage_users.php?message=User+deleted");
exit;