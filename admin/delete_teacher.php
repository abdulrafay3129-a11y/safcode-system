<?php
include("../config/init.php");
checkRole(['admin']);

$id = (int)($_GET['id'] ?? 0);

if(!$id){
    die("Invalid ID");
}

// GET TEACHER EMAIL
$stmt = $conn->prepare("SELECT email FROM teachers WHERE teacher_id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows == 0){
    die("Teacher not found");
}

$data = $res->fetch_assoc();
$email = $data['email'];

// DELETE FROM USERS FIRST
$delUser = $conn->prepare("DELETE FROM users WHERE email=?");
$delUser->bind_param("s",$email);
$delUser->execute();

// DELETE FROM TEACHERS
$delTeacher = $conn->prepare("DELETE FROM teachers WHERE teacher_id=?");
$delTeacher->bind_param("i",$id);

if($delTeacher->execute()){

    logActivity($_SESSION['user_id'], $_SESSION['role'], "Deleted teacher ($email)");

    header("Location: manage_teachers.php?msg=deleted");
    exit;

}else{
    echo "❌ Error: " . $conn->error;
}
?>