<?php
include("../config/init.php");
checkRole(['admin']);

logActivity($_SESSION['user_id'], $_SESSION['role'], "Viewed Pending Fees");

/* ===================== */
$filter_month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$filter_year  = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$current_time  = strtotime(date('Y-m-d'));
$selected_time = strtotime($filter_year . '-' . $filter_month . '-01');

/* ❌ IF FUTURE MONTH SELECTED → NO DATA */
if($selected_time > $current_time){
    $pending_data = [];
} else {

    /* COURSES */
    $courses = $conn->query("SELECT id,name FROM courses WHERE status=1");

    /* FILTERS */
    $where = "WHERE s.status='active'";
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

    /* MULTIPLE COURSES */
    $sql = "
    SELECT 
        s.id,
        s.gr_no,
        s.name,
        sch.course_id,   /* 🔥 FIX ADDED */
        sch.admission_date,
        c.name AS course_name,
        c.fees AS course_fee,
        c.duration_months,
        sch.days,
        sch.time_slot
    FROM students s
    JOIN student_course_history sch ON sch.student_id = s.id
    JOIN courses c ON c.id = sch.course_id
    $where
    ORDER BY s.id DESC
    ";

    $stmt = $conn->prepare($sql);
    if($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $pending_data = [];

    while($row = $res->fetch_assoc()){

        /* admission month normalize */
        $admission = strtotime(date('Y-m-01', strtotime($row['admission_date'])));
        $duration  = (int)$row['duration_months'];

        /* course END month */
        $end_month = strtotime("+".($duration-1)." month", $admission);

        /* ❌ IF selected month OUTSIDE COURSE → skip */
        if($selected_time < $admission || $selected_time > $end_month){
            continue;
        }

        /* ❌ IF selected month FUTURE → skip */
        if($selected_time > $current_time){
            continue;
        }

        $m = (int)date('n', $selected_time);
        $y = (int)date('Y', $selected_time);

        /* 🔥 FIXED FEES CHECK (WITH COURSE_ID) */
        $fee = $conn->prepare("
            SELECT total_fee, paid_fee
            FROM fees
            WHERE student_id=? 
            AND course_id=? 
            AND fee_month_num=? 
            AND fee_year=? 
            ORDER BY id DESC 
            LIMIT 1
        ");

        $fee->bind_param("iiii", 
            $row['id'], 
            $row['course_id'], 
            $m, 
            $y
        );

        $fee->execute();
        $f = $fee->get_result()->fetch_assoc();

        if($f){
            $due = (float)$f['total_fee'] - (float)$f['paid_fee'];
        } else {
            $due = (float)$row['course_fee'];
        }

        if($due <= 0) continue;

        $pending_data[] = [
            'gr_no' => $row['gr_no'],
            'name' => $row['name'],
            'course_name' => $row['course_name'],
            'days' => $row['days'],
            'time_slot' => $row['time_slot'],
            'month' => date("F", mktime(0,0,0,$m,1)),
            'year' => $y,
            'due' => $due
        ];
    }
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

<h3>📊 Pending Fees</h3>

<form method="GET" class="row g-2 mb-3" autocomplete="off">

<div class="col-md-2">
<input type="text" name="name" class="form-control" placeholder="Name"
value="<?= $_GET['name'] ?? '' ?>">
</div>

<div class="col-md-2">
<select name="course_id" class="form-select">
<option value="">Course</option>
<?php 
$courses = $conn->query("SELECT id,name FROM courses WHERE status=1");
while($c=$courses->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>" <?= (($_GET['course_id'] ?? '')==$c['id'])?'selected':'' ?>>
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-2">
<select name="days" class="form-select">
<option value="">Days</option>
<option value="MonWedFri" <?= (($_GET['days'] ?? '')=='MonWedFri')?'selected':'' ?>>MonWedFri</option>
<option value="TueThuSat" <?= (($_GET['days'] ?? '')=='TueThuSat')?'selected':'' ?>>TueThuSat</option>
</select>
</div>

<div class="col-md-2">
<select name="time_slot" class="form-select">
<option value="">Time</option>
<option value="3-4:30" <?= (($_GET['time_slot'] ?? '')=='3-4:30')?'selected':'' ?>>3-4:30</option>
<option value="4:30-6" <?= (($_GET['time_slot'] ?? '')=='4:30-6')?'selected':'' ?>>4:30-6</option>
<option value="6-7:30" <?= (($_GET['time_slot'] ?? '')=='6-7:30')?'selected':'' ?>>6-7:30</option>
<option value="7:30-9" <?= (($_GET['time_slot'] ?? '')=='7:30-9')?'selected':'' ?>>7:30-9</option>
</select>
</div>

<div class="col-md-2">
<select name="month" class="form-select">
<?php for($m=1;$m<=12;$m++): ?>
<option value="<?= $m ?>" <?= ($m==$filter_month)?'selected':'' ?>>
<?= date("F", mktime(0,0,0,$m,1)) ?>
</option>
<?php endfor; ?>
</select>
</div>

<div class="col-md-2">
<input type="number" name="year" class="form-control" value="<?= $filter_year ?>">
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
<th>Course</th>
<th>Days</th>
<th>Time</th>
<th>Month</th>
<th>Year</th>
<th>Due</th>
</tr>
</thead>

<tbody>
<?php if(!empty($pending_data)): ?>
<?php foreach($pending_data as $row): ?>
<tr>
<td><?= $row['gr_no'] ?></td>
<td><?= $row['name'] ?></td>
<td><?= $row['course_name'] ?></td>
<td><?= $row['days'] ?></td>
<td><?= $row['time_slot'] ?></td>
<td><?= $row['month'] ?></td>
<td><?= $row['year'] ?></td>
<td class="text-danger"><?= $row['due'] ?></td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr>
<td colspan="8" class="text-center">No Pending Fees</td>
</tr>
<?php endif; ?>
</tbody>

</table>

</div>
</div>

<?php include("../includes/footer.php"); ?>

</body>
</html>