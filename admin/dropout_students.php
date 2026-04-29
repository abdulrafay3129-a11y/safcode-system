<?php
include("../config/init.php");
checkRole(['admin','teacher']);

$status = $_GET['status'] ?? '';
$month  = $_GET['month'] ?? '';
$course = $_GET['course_id'] ?? '';

$sql = "
SELECT 
    s.id,
    s.name,
    s.status,
    s.status_date,
    COALESCE(c.name, 'No Course') AS course_name,
    sch.course_id
FROM students s

/* ✅ SHOW ALL COURSES */
LEFT JOIN student_course_history sch ON sch.student_id = s.id
LEFT JOIN courses c ON c.id = sch.course_id

WHERE 1=1
";

$params = [];
$types = "";

/* STATUS FILTER */
if ($status !== '') {
    $sql .= " AND s.status = ?";
    $types .= "s";
    $params[] = $status;
}

/* MONTH FILTER */
if (!empty($month)) {
    $sql .= " AND MONTH(s.status_date) = ?";
    $types .= "i";
    $params[] = (int)$month;
}

/* COURSE FILTER */
if (!empty($course)) {
    $sql .= " AND sch.course_id = ?";
    $types .= "i";
    $params[] = (int)$course;
}

$sql .= " ORDER BY s.id DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Student Status</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="d-flex">

<?php include("../includes/sidebar.php"); ?>

<div class="content p-4 w-100">

<h3>Student Status Management</h3>

<form method="GET" class="row g-2 mb-3">

<div class="col-md-3">
<select name="status" class="form-select">
<option value="">All</option>
<option value="active" <?= $status=='active'?'selected':'' ?>>Active</option>
<option value="inactive" <?= $status=='inactive'?'selected':'' ?>>Inactive</option>
<option value="dropout" <?= $status=='dropout'?'selected':'' ?>>Dropout</option>
<option value="certified" <?= $status=='certified'?'selected':'' ?>>Certified</option>
</select>
</div>

<div class="col-md-2">
<select name="month" class="form-select">
<option value="">Month</option>
<?php for($m=1;$m<=12;$m++): ?>
<option value="<?= $m ?>" <?= ($month==$m)?'selected':'' ?>>
<?= date("F", mktime(0,0,0,$m,1)) ?>
</option>
<?php endfor; ?>
</select>
</div>

<div class="col-md-3">
<select name="course_id" class="form-select">
<option value="">Course</option>
<?php
$c = $conn->query("SELECT id,name FROM courses");
while($r = $c->fetch_assoc()):
?>
<option value="<?= $r['id'] ?>" <?= ($course==$r['id'])?'selected':'' ?>>
<?= $r['name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-2">
<button class="btn btn-primary w-100">Filter</button>
</div>

</form>

<table class="table table-bordered">
<tr>
<th>Name</th>
<th>Course</th>
<th>Status</th>
<th>Date</th>
<th>Action</th>
</tr>

<?php while($row = $res->fetch_assoc()): ?>

<?php
$statusVal = $row['status'];

$badge = match($statusVal){
    'active' => 'success',
    'inactive' => 'secondary',
    'dropout' => 'danger',
    'certified' => 'dark',
    default => 'light'
};
?>

<tr>
<td><?= $row['name'] ?></td>
<td><?= $row['course_name'] ?></td>

<td>
<span class="badge bg-<?= $badge ?>">
<?= $statusVal ?>
</span>
</td>

<td><?= $row['status_date'] ?></td>

<td>

<?php if($statusVal !== 'active'): ?>
<a href="student_status_update.php?id=<?= $row['id'] ?>&status=active" class="btn btn-success btn-sm">Active</a>
<?php endif; ?>

<?php if($statusVal !== 'inactive'): ?>
<a href="student_status_update.php?id=<?= $row['id'] ?>&status=inactive" class="btn btn-secondary btn-sm">Inactive</a>
<?php endif; ?>

<?php if($statusVal !== 'dropout'): ?>
<a href="student_status_update.php?id=<?= $row['id'] ?>&status=dropout" class="btn btn-warning btn-sm">Dropout</a>
<?php endif; ?>

<?php if($statusVal !== 'certified'): ?>
<a href="student_status_update.php?id=<?= $row['id'] ?>&status=certified" class="btn btn-dark btn-sm">Certified</a>
<?php endif; ?>

</td>
</tr>

<?php endwhile; ?>

</table>

</div>
</div>
</body>
</html>