<?php
include("../config/init.php");
checkRole(['teacher']);

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];

// LOG
logActivity( $user_id, $_SESSION['role'], "Opened Teacher Dashboard");

// TOTAL STUDENTS (assigned to teacher via history)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT student_id) as total
    FROM student_course_history
    WHERE teacher_id=?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_students = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// TODAY ATTENDANCE MARKED
$stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM attendance
    WHERE marked_by=? AND attendance_date=CURDATE()
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$today_attendance = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
<title>Teacher Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="d-flex">
<?php include("../includes/sidebar.php"); ?>

<div class="content p-4 flex-grow-1">

<h3>Welcome <?= htmlspecialchars($name) ?></h3>

<div class="row mt-4">

<div class="col-md-4">
<div class="card bg-primary text-white p-3">
<h5>My Students</h5>
<h3><?= $total_students ?></h3>
</div>
</div>

<div class="col-md-4">
<div class="card bg-success text-white p-3">
<h5>Today's Attendance</h5>
<h3><?= $today_attendance ?></h3>
</div>
</div>

</div>

<hr>

<a href="view_students.php" class="btn btn-primary">View Students</a>
<a href="attendance.php" class="btn btn-warning">Mark Attendance</a>

</div>
</div>

<?php include("../includes/footer.php"); ?>
</body>
</html>