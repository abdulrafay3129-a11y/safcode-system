<?php
include("../config/init.php");
checkRole(['admin','teacher']);

$id = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';

if(!$id || !$status){
    die("Invalid request");
}

$allowed = ['active','dropout','inactive','completed','certified'];

if(!in_array($status, $allowed)){
    die("Invalid status");
}

$stmt = $conn->prepare("
    UPDATE students 
    SET status=?, status_date=NOW()
    WHERE id=?
");

$stmt->bind_param("si", $status, $id);
$stmt->execute();

logActivity($_SESSION['user_id'], $_SESSION['role'], 
    "Status changed to $status (Student ID: $id)"
);

header("Location: dropout_students.php");
exit;
?>