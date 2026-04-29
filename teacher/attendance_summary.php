<?php
include("../config/init.php");
checkRole(['teacher']);

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

logActivity($user_id,$role,"Viewed Attendance Summary");

$month = $_GET['month'] ?? date('Y-m');
$course_id = $_GET['course_id'] ?? '';
$days = $_GET['days'] ?? '';
$time_slot = $_GET['time_slot'] ?? '';

$courses = $conn->query("SELECT id,name FROM courses WHERE status=1");

/* ================= QUERY ================= */
$sql = "
SELECT 
s.id,
s.name,
s.father_name,
COUNT(a.id) AS total,
SUM(a.status='Present') AS present,
SUM(a.status='Absent') AS absent
FROM students s
INNER JOIN attendance a ON a.student_id = s.id
INNER JOIN student_course_history sch ON sch.student_id = s.id
WHERE s.status=1
AND DATE_FORMAT(a.attendance_date,'%Y-%m')=?
";

$params = [$month];
$types = "s";

/* FILTERS */
if($course_id){
    $sql.=" AND sch.course_id=?";
    $types.="i"; $params[]=$course_id;
}
if($days){
    $sql.=" AND sch.days=?";
    $types.="s"; $params[]=$days;
}
if($time_slot){
    $sql.=" AND sch.time_slot=?";
    $types.="s"; $params[]=$time_slot;
}

/* ONLY LATEST COURSE */
$sql .= " AND sch.id = (
    SELECT MAX(id) FROM student_course_history WHERE student_id=s.id
)";

$sql .= " GROUP BY s.id ORDER BY s.name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$data = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Attendance Summary</title>
<link rel="stylesheet" href="../assets/css/admin.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<div class="wrapper">
<?php include("../includes/sidebar.php"); ?>

<div class="content">

<h3>📈 Monthly Attendance Summary</h3>

<form method="GET" class="row g-2 mb-3">

<div class="col-md-2">
<input type="month" name="month" value="<?= $month ?>" class="form-control">
</div>

<div class="col-md-2">
<select name="course_id" class="form-select">
<option value="">Course</option>
<?php while($c=$courses->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>" <?= ($course_id==$c['id'])?'selected':'' ?>>
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-2">
<select name="days" class="form-select">
<option value="">Days</option>
<option value="MonWedFri" <?= ($days=='MonWedFri')?'selected':'' ?>>Mon Wed Fri</option>
<option value="TueThuSat" <?= ($days=='TueThuSat')?'selected':'' ?>>Tue Thu Sat</option>
</select>
</div>

<div class="col-md-2">
<select name="time_slot" class="form-select">
<option value="">Time</option>
<option value="3-4:30" <?= ($time_slot=='3-4:30')?'selected':'' ?>>3-4:30</option>
<option value="4:30-6" <?= ($time_slot=='4:30-6')?'selected':'' ?>>4:30-6</option>
<option value="6-7:30" <?= ($time_slot=='6-7:30')?'selected':'' ?>>6-7:30</option>
<option value="7:30-9" <?= ($time_slot=='7:30-9')?'selected':'' ?>>7:30-9</option>
</select>
</div>

<div class="col-md-2">
<button class="btn btn-primary w-100">Filter</button>
</div>

</form>

<table class="table table-bordered">
<thead class="table-dark">
<tr>
<th>GR</th>
<th>Name</th>
<th>Father</th>
<th>Present</th>
<th>Absent</th>
<th>%</th>
</tr>
</thead>

<tbody>

<?php if($data->num_rows): ?>
<?php while($r=$data->fetch_assoc()): 
$total = $r['total'] ?: 1;
$per = round(($r['present']/$total)*100);
?>

<tr>
<td><?= $r['id'] ?></td>
<td><?= $r['name'] ?></td>
<td><?= $r['father_name'] ?></td>
<td><?= $r['present'] ?></td>
<td><?= $r['absent'] ?></td>
<td>
<span class="badge bg-<?= $per>=75?'success':($per>=50?'warning':'danger') ?>">
<?= $per ?>%
</span>
</td>
</tr>

<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="6" class="text-center">No Data Found</td>
</tr>
<?php endif; ?>

</tbody>
</table>

</div>
</div>

<?php include("../includes/footer.php"); ?>
</body>
</html>