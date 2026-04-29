<?php
include("../config/init.php");
checkRole(['student']);

$user_id = $_SESSION['user_id'];

/* STUDENT ID */
$stmt=$conn->prepare("SELECT id FROM students WHERE email=(SELECT email FROM users WHERE id=?)");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$student_id=$stmt->get_result()->fetch_assoc()['id'];

logActivity($user_id,$_SESSION['role'],"Viewed Fees");

/* COURSES */
$courses=$conn->query("
SELECT sch.course_id, c.name 
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

/* FEES (🔥 FIXED: fee_month_num use hoga) */
$stmt=$conn->prepare("
SELECT 
    fee_month_num,
    fee_year,
    total_fee,
    paid_fee,
    payment_date
FROM fees
WHERE student_id=? AND course_id=?
ORDER BY fee_year DESC, fee_month_num DESC
");
$stmt->bind_param("ii",$student_id,$selected);
$stmt->execute();
$res=$stmt->get_result();

/* MONTH NAMES */
$months = [
1=>"January",2=>"February",3=>"March",4=>"April",5=>"May",6=>"June",
7=>"July",8=>"August",9=>"September",10=>"October",11=>"November",12=>"December"
];
?>

<!DOCTYPE html>
<html>
<head>
<title>My Fees</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="d-flex">
<?php include("../includes/sidebar.php"); ?>

<div class="container mt-4">

<h3>💰 My Fees</h3>

<form method="GET" class="mb-3">
<select name="course" onchange="this.form.submit()" class="form-select">
<?php while($c=$courses->fetch_assoc()): ?>
<option value="<?= $c['course_id'] ?>" <?= ($selected==$c['course_id'])?'selected':'' ?>>
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>
</form>

<table class="table table-bordered">
<tr>
<th>Month</th>
<th>Total</th>
<th>Paid</th>
<th>Pending</th>
<th>Status</th>
</tr>

<?php if($res->num_rows): ?>
<?php while($f=$res->fetch_assoc()): 

$monthNum = (int)$f['fee_month_num'];
$monthName = $months[$monthNum] ?? 'Invalid';

$status = ($f['paid_fee'] >= $f['total_fee']) ? "Paid" : "Unpaid";

?>
<tr>
<td><?= $monthName . ' ' . $f['fee_year'] ?></td>
<td><?= $f['total_fee'] ?></td>
<td><?= $f['paid_fee'] ?></td>
<td><?= $f['total_fee'] - $f['paid_fee'] ?></td>
<td>
<span class="badge bg-<?= $status=='Paid'?'success':'danger' ?>">
<?= $status ?>
</span>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="5" class="text-center">No record</td></tr>
<?php endif; ?>

</table>

</div>
</div>
</body>
</html>