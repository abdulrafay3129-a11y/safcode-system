<?php
include("../config/init.php");
checkRole(['admin']);

$user_id=$_SESSION['user_id'];
$role=$_SESSION['role'];

$student_id=$_GET['student_id'] ?? '';
$filter_date=$_GET['att_date'] ?? '';

if(!$student_id){
header("Location: attendance_view.php");
exit;
}

/* STUDENT */
$stmt=$conn->prepare("
SELECT s.name,s.father_name,c.name AS course_name,sch.days,sch.time_slot
FROM students s
LEFT JOIN student_course_history sch ON sch.student_id=s.id
LEFT JOIN courses c ON c.id=sch.course_id
WHERE s.id=? ORDER BY sch.id DESC LIMIT 1
");
$stmt->bind_param("i",$student_id);
$stmt->execute();
$student=$stmt->get_result()->fetch_assoc();

/* UPDATE */
if(isset($_POST['update'])){
$id=$_POST['id'];
$status=$_POST['status'];

$conn->query("UPDATE attendance SET status='$status' WHERE id=$id");

header("Location: ?student_id=$student_id&msg=updated");
}

/* FETCH */
$sql="SELECT * FROM attendance WHERE student_id=$student_id";

if($filter_date){
$sql.=" AND attendance_date='".$conn->real_escape_string($filter_date)."'";
}

$sql.=" ORDER BY attendance_date DESC";

$data=$conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>Detail</title>
<link rel="stylesheet" href="../assets/css/admin.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<div class="wrapper">
<?php include("../includes/sidebar.php"); ?>

<div class="content">

<h3>📄 Attendance Detail</h3>

<?php if(isset($_GET['msg'])): ?>
<div class="alert alert-success">Updated Successfully</div>
<?php endif; ?>

<div class="card mb-3 p-3">
<b><?= $student['name'] ?></b><br>
Father: <?= $student['father_name'] ?><br>
Course: <?= $student['course_name'] ?><br>
Days: <?= $student['days'] ?><br>
Time: <?= $student['time_slot'] ?>
</div>

<form class="row mb-3">
<input type="hidden" name="student_id" value="<?= $student_id ?>">
<div class="col-md-3">
<input type="date" name="att_date" value="<?= $filter_date ?>" class="form-control">
</div>
<div class="col-md-2">
<button class="btn btn-info w-100">Filter</button>
</div>
</form>

<table class="table table-bordered">
<tr>
<th>Date</th>
<th>Status</th>
<th>Save</th>
</tr>

<?php while($r=$data->fetch_assoc()): ?>
<tr>

<td><?= $r['attendance_date'] ?></td>

<td>
<form method="POST" class="d-flex gap-2">
<input type="hidden" name="id" value="<?= $r['id'] ?>">

<label><input type="radio" name="status" value="Present" <?= $r['status']=='Present'?'checked':'' ?>> P</label>
<label><input type="radio" name="status" value="Absent" <?= $r['status']=='Absent'?'checked':'' ?>> A</label>
</td>

<td>
<button name="update" class="btn btn-success btn-sm">Save</button>
</form>
</td>

</tr>
<?php endwhile; ?>

</table>

</div>
</div>

<?php include("../includes/footer.php"); ?>
</body>
</html>