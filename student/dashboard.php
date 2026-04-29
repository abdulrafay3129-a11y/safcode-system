<?php
include("../config/init.php");
checkRole(['student']);

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Student';

/* STUDENT ID */
$stmt = $conn->prepare("SELECT id FROM students WHERE email=(SELECT email FROM users WHERE id=?)");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$student_id = $stmt->get_result()->fetch_assoc()['id'] ?? 0;

logActivity($user_id,$_SESSION['role'],"Opened Dashboard");

/* COURSES */
$courses = $conn->prepare("
SELECT sch.id, sch.course_id, c.name
FROM student_course_history sch
JOIN courses c ON c.id=sch.course_id
WHERE sch.student_id=?
");
$courses->bind_param("i",$student_id);
$courses->execute();
$coursesData = $courses->get_result();

/* SELECTED COURSE */
$selected_course = $_GET['course'] ?? 0;

if(!$selected_course && $coursesData->num_rows){
    $first = $coursesData->fetch_assoc();
    $selected_course = $first['id'];
    $coursesData->data_seek(0);
}

/* COURSE DETAIL */
$stmt = $conn->prepare("
SELECT sch.*, c.name as course_name
FROM student_course_history sch
JOIN courses c ON c.id=sch.course_id
WHERE sch.id=?
");
$stmt->bind_param("i",$selected_course);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

$course_id = $course['course_id'] ?? 0;

/* FEES TOTAL */
$stmt = $conn->prepare("
SELECT SUM(total_fee) as total_fee, SUM(paid_fee) as paid
FROM fees WHERE student_id=? AND course_id=?
");
$stmt->bind_param("ii",$student_id,$course_id);
$stmt->execute();
$f = $stmt->get_result()->fetch_assoc();

$total_fee = $f['total_fee'] ?? 0;
$paid = $f['paid'] ?? 0;
$pending = $total_fee - $paid;

/* CURRENT MONTH STATUS */
$month = date('n');
$year = date('Y');

$stmt = $conn->prepare("
SELECT SUM(total_fee) as t, SUM(paid_fee) as p
FROM fees 
WHERE student_id=? AND course_id=? AND fee_month_num=? AND fee_year=?
");
$stmt->bind_param("iiii",$student_id,$course_id,$month,$year);
$stmt->execute();
$cm = $stmt->get_result()->fetch_assoc();

$status = ($cm['t']>0 && $cm['p'] >= $cm['t']) ? "Paid" : "Unpaid";

/* ATTENDANCE */
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE student_id=? AND course_id=?");
$stmt->bind_param("ii",$student_id,$course_id);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="d-flex">
<?php include("../includes/sidebar.php"); ?>

<div class="p-4 w-100">

<h3>Welcome <?= $name ?></h3>

<form method="GET" class="mb-3">
<select name="course" class="form-select" onchange="this.form.submit()">
<?php while($c=$coursesData->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>" <?= ($selected_course==$c['id'])?'selected':'' ?>>
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>
</form>

<div class="row">
<div class="col-md-3"><div class="card bg-primary text-white p-3">Total Fee<br><?= $total_fee ?></div></div>
<div class="col-md-3"><div class="card bg-success text-white p-3">Paid<br><?= $paid ?></div></div>
<div class="col-md-3"><div class="card bg-danger text-white p-3">Pending<br><?= $pending ?></div></div>
<div class="col-md-3"><div class="card bg-warning p-3">Attendance<br><?= $attendance ?></div></div>
</div>

<div class="card mt-3 p-3">
<b>Course:</b> <?= $course['course_name'] ?><br>
<b>Status:</b> <?= $status ?><br>
</div>

</div>
</div>
</body>
</html>