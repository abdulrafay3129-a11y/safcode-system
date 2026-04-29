<?php
include("../config/init.php");
checkRole(['student']);

$user_id=$_SESSION['user_id'];

/* STUDENT ID */
$stmt=$conn->prepare("SELECT id FROM students WHERE email=(SELECT email FROM users WHERE id=?)");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$student_id=$stmt->get_result()->fetch_assoc()['id'];

logActivity($user_id,$_SESSION['role'],"Viewed Attendance");

/* COURSES */
$courses=$conn->query("
SELECT sch.course_id,c.name
FROM student_course_history sch
JOIN courses c ON c.id=sch.course_id
WHERE sch.student_id=$student_id
");

$selected=$_GET['course'] ?? 0;

if(!$selected && $courses->num_rows){
    $first=$courses->fetch_assoc();
    $selected=$first['course_id'];
    $courses->data_seek(0);
}

/* FILTERS */
$month = $_GET['month'] ?? '';
$from  = $_GET['from'] ?? '';
$to    = $_GET['to'] ?? '';

$sql = "
SELECT attendance_date,status
FROM attendance
WHERE student_id=? AND course_id=?
";

$params = [$student_id,$selected];
$types = "ii";

if($month){
    $sql.=" AND DATE_FORMAT(attendance_date,'%Y-%m')=?";
    $types.="s";
    $params[]=$month;
}

if($from && $to){
    $sql.=" AND attendance_date BETWEEN ? AND ?";
    $types.="ss";
    $params[]=$from;
    $params[]=$to;
}

$sql.=" ORDER BY attendance_date DESC";

$stmt=$conn->prepare($sql);
$stmt->bind_param($types,...$params);
$stmt->execute();
$res=$stmt->get_result();

/* COUNT */
$present=0;
$absent=0;

$data=[];
while($row=$res->fetch_assoc()){
    if($row['status']=='Present') $present++;
    else $absent++;
    $data[]=$row;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>My Attendance</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="d-flex">
<?php include("../includes/sidebar.php"); ?>

<div class="container mt-4">

<h3>📅 My Attendance</h3>

<form method="GET" class="row g-2 mb-3">

<div class="col-md-3">
<select name="course" class="form-select">
<?php while($c=$courses->fetch_assoc()): ?>
<option value="<?= $c['course_id'] ?>" <?= ($selected==$c['course_id'])?'selected':'' ?>>
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-3">
<input type="month" name="month" value="<?= $month ?>" class="form-control">
</div>

<div class="col-md-2">
<input type="date" name="from" value="<?= $from ?>" class="form-control">
</div>

<div class="col-md-2">
<input type="date" name="to" value="<?= $to ?>" class="form-control">
</div>

<div class="col-md-2">
<button class="btn btn-primary w-100">Filter</button>
</div>

</form>

<div class="mb-3">
<b>Present:</b> <?= $present ?> | 
<b>Absent:</b> <?= $absent ?>
</div>

<table class="table table-bordered">
<tr>
<th>Date</th>
<th>Status</th>
</tr>

<?php if(!empty($data)): ?>
<?php foreach($data as $a): ?>
<tr>
<td><?= $a['attendance_date'] ?></td>
<td>
<span class="badge bg-<?= $a['status']=='Present'?'success':'danger' ?>">
<?= $a['status'] ?>
</span>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="2" class="text-center">No record</td></tr>
<?php endif; ?>

</table>

</div>
</div>
</body>
</html>