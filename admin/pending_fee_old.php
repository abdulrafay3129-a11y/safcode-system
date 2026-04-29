<?php
include("../config/init.php");
checkRole(['admin']);

logActivity($_SESSION['user_id'], $_SESSION['role'], "Viewed Pending Fees");

$current_month = date('n');
$current_year  = date('Y');

/* ================= FILTERS ================= */
$where = "WHERE s.status='active' ";
$params = [];
$types = "";

if(!empty($_GET['name'])){
    $where .= " AND s.name LIKE ?";
    $types .= "s";
    $params[] = "%".$_GET['name']."%";
}

if(!empty($_GET['course_id'])){
    $where .= " AND sch.course_id=?";
    $types .= "i";
    $params[] = $_GET['course_id'];
    echo $where;
    // die();
}

if(!empty($_GET['days'])){
    $where .= " AND sch.days=?";
    $types .= "s";
    $params[] = $_GET['days'];
}

if(!empty($_GET['time_slot'])){
    $where .= " AND sch.time_slot=?";
    $types .= "s";
    $params[] = $_GET['time_slot'];
}

$filter_month = $_GET['month'] ?? $current_month;
$filter_year  = $_GET['year'] ?? $current_year;

/* ================= COURSES ================= */
$courses = $conn->query("SELECT id,name FROM courses ORDER BY name ASC");

/* ================= MAIN QUERY ================= */
$sql = "
SELECT 
    s.id,
    s.name,
    sch.admission_date,
    c.name AS course_name,
    c.fees AS course_fee,
    c.duration_months,
    sch.days,
    sch.time_slot
FROM students s
JOIN student_course_history sch ON s.id=sch.student_id
JOIN courses c ON c.id=sch.course_id
$where
GROUP BY s.id
ORDER BY s.id DESC
";

$stmt = $conn->prepare($sql);
if($params){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$pending_data = [];

while($row = $result->fetch_assoc()){

    $admission = $row['admission_date'];
    $end_date = date('Y-m-d', strtotime("+{$row['duration_months']} months", strtotime($admission)));

    $target_date = date("Y-m-d", strtotime("$filter_year-$filter_month-01"));

    /* ❌ SKIP IF OUTSIDE COURSE RANGE */
    if($target_date < $admission || $target_date > $end_date){
        continue;
    }

    $month_name = date("F", mktime(0,0,0,$filter_month,1,$filter_year));

    $check = $conn->prepare("
        SELECT total_fee, paid_fee 
        FROM fees 
        WHERE student_id=? 
        AND fee_month=? 
        AND fee_year=?
        ORDER BY id DESC LIMIT 1
    ");

    $check->bind_param("iss", $row['id'], $month_name, $filter_year);
    $check->execute();
    $fee = $check->get_result()->fetch_assoc();

    $due = $row['course_fee'];

    if($fee){
        $due = $fee['total_fee'] - $fee['paid_fee'];
        if($due <= 0) continue; // ❌ fully paid → NO SHOW
    }

    $row['month'] = $month_name;
    $row['year'] = $filter_year;
    $row['due'] = $due;

    $pending_data[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Pending Fees</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="d-flex">
<?php include("../includes/sidebar.php"); ?>

<div class="content p-4 w-100">

<h3>Pending Fees</h3>

<!-- FILTERS -->
<form method="GET" class="row g-2 mb-3">

<div class="col-md-2">
<input type="text" name="name" class="form-control" placeholder="Name">
</div>

<div class="col-md-2">
<select name="course_id" class="form-select">
<option value="">Course</option>
<?php while($c=$courses->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-2">
<select name="days" class="form-select">
<option value="">Days</option>
<option value="MonWedFri">Mon Wed Fri</option>
<option value="TueThuSat">Tue Thu Sat</option>
</select>
</div>

<div class="col-md-2">
<select name="time_slot" class="form-select">
<option value="">Time</option>
<option value="3-4:30">3-4:30</option>
<option value="4:30-6">4:30-6</option>
<option value="6-7:30">6-7:30</option>
<option value="7:30-9">7:30-9</option>
</select>
</div>

<div class="col-md-1">
<select name="month" class="form-select">
<?php for($m=1;$m<=12;$m++): ?>
<option value="<?= $m ?>" <?= ($m==$filter_month)?'selected':'' ?>>
<?= date("F", mktime(0,0,0,$m,1)) ?>
</option>
<?php endfor; ?>
</select>
</div>

<div class="col-md-1">
<input type="number" name="year" value="<?= $filter_year ?>" class="form-control">
</div>

<div class="col-md-2">
<button class="btn btn-primary w-100">Filter</button>
</div>

</form>

<!-- TABLE -->
<table class="table table-bordered">
<thead class="table-dark">
<tr>
<th>ID</th>
<th>Name</th>
<th>Course</th>
<th>Month</th>
<th>Year</th>
<th>Due</th>
<th>Action</th>
</tr>
</thead>

<tbody>
<?php foreach($pending_data as $row): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['name'] ?></td>
<td><?= $row['course_name'] ?></td>
<td><?= $row['month'] ?></td>
<td><?= $row['year'] ?></td>
<td class="text-danger"><?= $row['due'] ?></td>

<td>
<a href="pay_fee.php?id=<?= $row['id'] ?>&month=<?= $row['month'] ?>&year=<?= $row['year'] ?>"
class="btn btn-success btn-sm">
Pay
</a>
</td>

</tr>
<?php endforeach; ?>
</tbody>

</table>

</div>
</div>

</body>
</html> 