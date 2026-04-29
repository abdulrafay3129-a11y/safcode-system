<?php
include("../config/init.php");
checkRole(['admin']);

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$student_id = $_POST['student_id'] ?? 0;

if(!$student_id){
echo "error";
exit;
}

$date = date('Y-m-d');

/*
👉 SIMPLE AUTO ATTENDANCE RULE:
Present mark by default on scan
*/

$stmt = $conn->prepare("SELECT id FROM attendance WHERE student_id=? AND attendance_date=?");
$stmt->bind_param("is",$student_id,$date);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows > 0){

// update
$u = $conn->prepare("UPDATE attendance SET status='Present' WHERE student_id=? AND attendance_date=?");
$u->bind_param("is",$student_id,$date);
$u->execute();

}else{

// insert
$u = $conn->prepare("INSERT INTO attendance
(student_id,course_id,days,time_slot,status,attendance_date,marked_by,role)
VALUES (?,?,?,?,?,?,?,?)");

// optional fields null (hard copy system)
$course_id = 0;
$days = '';
$time_slot = '';

$status = 'Present';

$u->bind_param("iissssss",
$student_id,
$course_id,
$days,
$time_slot,
$status,
$date,
$user_id,
$role);

$u->execute();
}

echo "success";
exit;