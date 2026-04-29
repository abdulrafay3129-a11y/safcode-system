<?php
include("../config/init.php");
checkRole(['admin','teacher']);

$id = intval($_GET['id']);
$status = $_GET['status'];

$allowed = ['active','inactive','dropout','completed','certified'];

if(!in_array($status, $allowed)){
    die("Invalid status");
}

$stmt = $conn->prepare("UPDATE students SET status=?, status_date=NOW() WHERE id=?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();

logActivity($_SESSION['user_id'], $_SESSION['role'], "Status changed to $status | Student ID: $id");

header("Location: drop_students.php?status=$status");
exit;