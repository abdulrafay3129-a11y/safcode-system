<?php
include("../config/init.php");
requireLogin();
checkRole(['admin']);

/* ================= COURSES ================= */
$courses = $conn->query("SELECT id, name FROM courses WHERE status=1");

/* ================= FILTERS ================= */
$name = $_GET['name'] ?? '';
$course_id = $_GET['course_id'] ?? '';
$days = $_GET['days'] ?? '';
$time_slot = $_GET['time_slot'] ?? '';
$type = $_GET['type'] ?? 'paid'; // 🔥 DEFAULT PAID
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

/* ================= BASE ================= */
$where = "WHERE s.status='active'";
$params = [];
$types = "";

/* FILTERS */
if($name){
    $where .= " AND s.name LIKE ?";
    $types .= "s";
    $params[] = "%$name%";
}

if($course_id){
    $where .= " AND sch.course_id=?";
    $types .= "i";
    $params[] = $course_id;
}

if($days){
    $where .= " AND sch.days=?";
    $types .= "s";
    $params[] = $days;
}

if($time_slot){
    $where .= " AND sch.time_slot=?";
    $types .= "s";
    $params[] = $time_slot;
}

/* ================= QUERY ================= */
$sql = "
SELECT 
    s.id,
    s.name,
    sch.course_id,
    sch.admission_date,
    sch.course_fee,
    c.name AS course_name,
    c.duration_months,
    sch.days,
    sch.time_slot
FROM students s
JOIN student_course_history sch ON sch.student_id = s.id
JOIN courses c ON c.id = sch.course_id
$where
";

$stmt = $conn->prepare($sql);
if($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

/* ================= OUTPUT ================= */
$data = [];

$total = 0;
$paid = 0;
$pending = 0;

$current_month = strtotime(date('Y-m-01'));

while($row = $res->fetch_assoc()){

    $admission = strtotime(date('Y-m-01', strtotime($row['admission_date'])));
    $duration  = (int)$row['duration_months'];

    for($i=0; $i<$duration; $i++){

        $month_time = strtotime("+$i month", $admission);

        /* ❌ FUTURE SKIP */
        if($month_time > $current_month){
            continue;
        }

        /* DATE FILTER */
        if($from_date && $to_date){
            if($month_time < strtotime($from_date) || $month_time > strtotime($to_date)){
                continue;
            }
        }

        $m = date('n', $month_time);
        $y = date('Y', $month_time);

        /* FEES */
        $fee = $conn->prepare("
            SELECT total_fee, paid_fee 
            FROM fees 
            WHERE student_id=? 
            AND course_id=? 
            AND fee_month_num=? 
            AND fee_year=? 
            ORDER BY id DESC LIMIT 1
        ");

        $fee->bind_param("iiii",
            $row['id'],
            $row['course_id'],
            $m,
            $y
        );

        $fee->execute();
        $f = $fee->get_result()->fetch_assoc();

        $course_fee = (float)$row['course_fee'];

        if($f){
            $paid_fee = (float)$f['paid_fee'];
            $total_fee = (float)$f['total_fee'];
        } else {
            $paid_fee = 0;
            $total_fee = $course_fee;
        }

        $due = $total_fee - $paid_fee;

        /* TYPE FILTER */
        if($type == 'paid' && $due > 0) continue;
        if($type == 'pending' && $due <= 0) continue;

        $total += $total_fee;
        $paid += $paid_fee;
        $pending += max(0, $due);

        $data[] = [
            'name' => $row['name'],
            'course' => $row['course_name'],
            'days' => $row['days'],
            'time' => $row['time_slot'],
            'month' => date("M Y", $month_time),
            'total' => $total_fee,
            'paid' => $paid_fee,
            'pending' => $due
        ];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Monthly Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>

<body>

<div class="d-flex">
<?php include("../includes/sidebar.php"); ?>

<div class="content p-4 w-100">

<h3>📊 Monthly Fee Dashboard</h3>

<!-- FILTERS -->
<form method="GET" class="row g-2 mb-3" autocomplete="off">

<input type="text" name="name" value="<?= $name ?>" class="form-control col" placeholder="Name" autocomplete="off">

<select name="course_id" class="form-select col">
<option value="">Course</option>
<?php while($c=$courses->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>" <?= ($course_id==$c['id'])?'selected':'' ?>>
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>

<select name="days" class="form-select col">
<option value="">Days</option>
<option value="MonWedFri" <?= ($days=='MonWedFri')?'selected':'' ?>>MonWedFri</option>
<option value="TueThuSat" <?= ($days=='TueThuSat')?'selected':'' ?>>TueThuSat</option>
</select>

<select name="time_slot" class="form-select col">
<option value="">Time</option>
<option value="3-4:30" <?= ($time_slot=='3-4:30')?'selected':'' ?>>3-4:30</option>
<option value="4:30-6" <?= ($time_slot=='4:30-6')?'selected':'' ?>>4:30-6</option>
</select>

<select name="type" class="form-select col">
<option value="all" <?= ($type=='all')?'selected':'' ?>>All</option>
<option value="paid" <?= ($type=='paid')?'selected':'' ?>>Paid</option>
<option value="pending" <?= ($type=='pending')?'selected':'' ?>>Pending</option>
</select>

<input type="date" name="from_date" value="<?= $from_date ?>" class="form-control col" autocomplete="off">
<input type="date" name="to_date" value="<?= $to_date ?>" class="form-control col" autocomplete="off">

<button class="btn btn-primary col">Filter</button>

</form>

<!-- SUMMARY -->
<div class="row mb-3">
<div class="col-md-4"><div class="card p-3">Total: <?= number_format($total) ?></div></div>
<div class="col-md-4"><div class="card p-3 bg-success text-white">Paid: <?= number_format($paid) ?></div></div>
<div class="col-md-4"><div class="card p-3 bg-danger text-white">Pending: <?= number_format($pending) ?></div></div>
</div>

<!-- TABLE -->
<table id="tbl" class="table table-bordered">
<thead>
<tr>
<th>Name</th>
<th>Course</th>
<th>Days</th>
<th>Time</th>
<th>Month</th>
<th>Total</th>
<th>Paid</th>
<th>Pending</th>
</tr>
</thead>

<tbody>
<?php foreach($data as $d): ?>
<tr>
<td><?= $d['name'] ?></td>
<td><?= $d['course'] ?></td>
<td><?= $d['days'] ?></td>
<td><?= $d['time'] ?></td>
<td><?= $d['month'] ?></td>
<td><?= $d['total'] ?></td>
<td><?= $d['paid'] ?></td>
<td><?= $d['pending'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>
</div>

<script>
$(function(){
    $('#tbl').DataTable({
        pageLength: 10
    });
});
</script>

<?php include("../includes/footer.php"); ?>

</body>
</html>