<?php
include("../config/init.php");
checkRole(['admin']);

header('Content-Type: application/json');

$code = $_GET['code'] ?? '';
$date = date('Y-m-d');

if(!$code){
    echo json_encode(['success'=>false,'msg'=>'No code']);
    exit;
}

/* find student by GR_NO */
$stmt = $conn->prepare("SELECT id FROM students WHERE gr_no=? AND status=1");
$stmt->bind_param("s",$code);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows == 0){
    echo json_encode(['success'=>false,'msg'=>'Student not found']);
    exit;
}

$student = $res->fetch_assoc();
$sid = $student['id'];

/* AUTO MARK PRESENT (NO COURSE REQUIRED) */
$check = $conn->prepare("SELECT id FROM attendance WHERE student_id=? AND attendance_date=?");
$check->bind_param("is",$sid,$date);
$check->execute();
$exists = $check->get_result()->num_rows;

if($exists){
    $upd = $conn->prepare("UPDATE attendance SET status='Present' WHERE student_id=? AND attendance_date=?");
    $upd->bind_param("is",$sid,$date);
    $upd->execute();
}else{
    $ins = $conn->prepare("INSERT INTO attendance
    (student_id,status,attendance_date,marked_by,role)
    VALUES (?,?,?,?,?)");

    $role = $_SESSION['role'];
    $user = $_SESSION['user_id'];

    $status = "Present";
    $ins->bind_param("issis",$sid,$status,$date,$user,$role);
    $ins->execute();
}

echo json_encode([
    'success'=>true,
    'msg'=>'Attendance Saved Successfully',
    'student_id'=>$sid
]);