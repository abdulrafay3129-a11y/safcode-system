<?php
include("../config/init.php");

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["success"=>false]);
    exit;
}

$code = $_GET['code'] ?? '';

if(!$code){
    echo json_encode(["success"=>false]);
    exit;
}

/* ✅ STUDENT */
$stmt = $conn->prepare("SELECT id, name FROM students WHERE gr_no=? AND status=1 LIMIT 1");
$stmt->bind_param("s",$code);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows == 0){
    echo json_encode(["success"=>false,"msg"=>"Student not found"]);
    exit;
}

$row = $res->fetch_assoc();

$student_id = $row['id'];
$name = $row['name'];

$date = date('Y-m-d');

/* ✅ GET ALL COURSES OF STUDENT */
$courses = $conn->prepare("
SELECT course_id, days, time_slot 
FROM student_course_history 
WHERE student_id=?
");
$courses->bind_param("i",$student_id);
$courses->execute();
$cRes = $courses->get_result();

/* ❗ AGAR COURSE HI NA HO */
if($cRes->num_rows == 0){
    echo json_encode(["success"=>false,"msg"=>"No course found"]);
    exit;
}

/* ✅ LOOP ALL COURSES */
while($c = $cRes->fetch_assoc()){

    $course_id = $c['course_id'];
    $days = $c['days'];
    $time_slot = $c['time_slot'];

    /* CHECK */
    $check = $conn->prepare("
    SELECT id FROM attendance 
    WHERE student_id=? AND attendance_date=? 
    AND course_id=? AND days=? AND time_slot=?
    ");
    $check->bind_param("isiss",$student_id,$date,$course_id,$days,$time_slot);
    $check->execute();

    if($check->get_result()->num_rows > 0){

        $upd = $conn->prepare("
        UPDATE attendance 
        SET status='Present' 
        WHERE student_id=? AND attendance_date=? 
        AND course_id=? AND days=? AND time_slot=?
        ");
        $upd->bind_param("isiss",$student_id,$date,$course_id,$days,$time_slot);
        $upd->execute();

    }else{

        $user = $_SESSION['user_id'];
        $role = $_SESSION['role'];

        $ins = $conn->prepare("
        INSERT INTO attendance 
        (student_id, course_id, days, time_slot, status, attendance_date, marked_by, role)
        VALUES (?, ?, ?, ?, 'Present', ?, ?, ?)
        ");

        $ins->bind_param("iisssis",$student_id,$course_id,$days,$time_slot,$date,$user,$role);
        $ins->execute();
    }
}

/* ✅ FINAL RESPONSE */
echo json_encode([
    "success"=>true,
    "id"=>$student_id,
    "name"=>$name
]);