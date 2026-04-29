<?php
include("../config/init.php");
checkRole(['student']);

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

/* STUDENT ID */
$stmt = $conn->prepare("
    SELECT id FROM students 
    WHERE email = (SELECT email FROM users WHERE id=?)
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$student_id = $res['id'] ?? 0;

/* LOGGER */
logActivity($user_id, $_SESSION['role'], "Fetched Dashboard Data");

/* SELECTED COURSE */
$course_id = $_GET['course_id'] ?? 0;

/* FEES (FIXED) */
$fees = [];

$stmt = $conn->prepare("
    SELECT 
        f.fee_month,
        f.fee_year,
        f.total_fee,
        f.paid_fee,
        f.payment_date,
        c.name as course_name
    FROM fees f
    JOIN courses c ON c.id = f.course_id
    WHERE f.student_id = ? AND f.course_id=?
    ORDER BY f.id DESC
");

$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute();
$res = $stmt->get_result();

while($row = $res->fetch_assoc()){
    $fees[] = $row;
}

/* ATTENDANCE (FIXED) */
$attendance = [];

$stmt2 = $conn->prepare("
    SELECT attendance_date, status
    FROM attendance
    WHERE student_id = ? AND course_id=?
    ORDER BY id DESC
");

$stmt2->bind_param("ii", $student_id, $course_id);
$stmt2->execute();
$res2 = $stmt2->get_result();

while($row = $res2->fetch_assoc()){
    $attendance[] = $row;
}

echo json_encode([
    'fees' => $fees,
    'attendance' => $attendance
]);