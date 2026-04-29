<?php
include("../config/init.php");
checkRole(['receptionist']);

logActivity($_SESSION['user_id'], $_SESSION['role'], "Viewed Fee Records");

/* ===== KEEP FILTER STATE ===== */
$queryString = $_SERVER['QUERY_STRING'];

/* COURSES */
$courses = $conn->query("SELECT id,name FROM courses WHERE status=1");

$where = "";
$params = [];
$types = "";

if(isset($_GET['search'])){

    $where = "WHERE 1=1";

    if(!empty($_GET['course_id'])){
        $where .= " AND sch.course_id=?";
        $types.="i"; $params[]=$_GET['course_id'];
    }

    if(!empty($_GET['days'])){
        $where .= " AND sch.days=?";
        $types.="s"; $params[]=$_GET['days'];
    }

    if(!empty($_GET['time_slot'])){
        $where .= " AND sch.time_slot=?";
        $types.="s"; $params[]=$_GET['time_slot'];
    }

    if(!empty($_GET['name'])){
        $where .= " AND s.name LIKE ?";
        $types.="s"; $params[]="%".$_GET['name']."%";
    }

    if(!empty($_GET['gr_no'])){
        $where .= " AND s.gr_no LIKE ?";
        $types.="s"; $params[]="%".$_GET['gr_no']."%";
    }

    if(!empty($_GET['from_date'])){
        $where .= " AND DATE(f.payment_date)>=?";
        $types.="s"; $params[]=$_GET['from_date'];
    }

    if(!empty($_GET['to_date'])){
        $where .= " AND DATE(f.payment_date)<=?";
        $types.="s"; $params[]=$_GET['to_date'];
    }

    $sql = "
    SELECT 
        f.id AS fee_id,
        f.student_id,
        s.gr_no,
        s.name AS student_name,
        s.father_name,
        sch.days,
        sch.time_slot,
        sch.course_id,
        sch.admission_date,
        c.name AS course_name,
        f.fee_month_num,
        f.fee_year,
        f.total_fee,
        f.paid_fee,
        f.payment_date
    FROM fees f
    JOIN students s ON f.student_id=s.id
    JOIN student_course_history sch ON s.id=sch.student_id
    JOIN courses c ON sch.course_id=c.id
    INNER JOIN (
        SELECT student_id, MAX(id) latest
        FROM student_course_history
        GROUP BY student_id
    ) latest_sch ON sch.id = latest_sch.latest
    $where
    ORDER BY f.id DESC
    ";

    $stmt = $conn->prepare($sql);
    if($params){ $stmt->bind_param($types,...$params); }
    $stmt->execute();
    $fees = $stmt->get_result();

}else{
    $fees = false;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Fees</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
.slip-overlay{
position:fixed; top:0; left:0;
width:100%; height:100%;
background:rgba(0,0,0,0.6);
display:none; justify-content:center; align-items:center;
z-index:9999;
}

.slip{
width:380px;
background:#fff;
padding:15px;
border:2px solid #000;
}

.table-slip{
width:100%;
border-collapse:collapse;
}

.table-slip td{
border:1px solid #000;
padding:6px;
}

.logo{text-align:center;margin-bottom:10px;}

@media print{
body *{visibility:hidden;}
.slip-overlay{display:flex !important;}
.slip,.slip *{visibility:visible;}
.slip{
position:absolute;
top:50%;
left:50%;
transform:translate(-50%,-50%);
}
}
</style>
</head>

<body>

<div class="d-flex">
<?php include("../includes/sidebar.php"); ?>

<div class="content p-4 w-100">

<h4>Manage Fees</h4>

<!-- FILTER -->
<form method="GET" class="row g-2 mb-3" autocomplete="off">

<input name="name" class="form-control col" placeholder="Name" value="<?= $_GET['name'] ?? '' ?>">
<input name="gr_no" class="form-control col" placeholder="GR" value="<?= $_GET['gr_no'] ?? '' ?>">

<select name="course_id" class="form-select col">
<option value="">Course</option>
<?php 
$courses2 = $conn->query("SELECT id,name FROM courses WHERE status=1");
while($c=$courses2->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>" <?= (($_GET['course_id'] ?? '')==$c['id'])?'selected':'' ?>>
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>

<select name="days" class="form-select col">
<option value="">Days</option>
<option value="MonWedFri" <?= ($_GET['days'] ?? '')=='MonWedFri'?'selected':'' ?>>MonWedFri</option>
<option value="TueThuSat" <?= ($_GET['days'] ?? '')=='TueThuSat'?'selected':'' ?>>TueThuSat</option>
</select>

<select name="time_slot" class="form-select col">
<option value="">Time</option>
<option value="3-4:30" <?= ($_GET['time_slot'] ?? '')=='3-4:30'?'selected':'' ?>>3-4:30</option>
<option value="4:30-6" <?= ($_GET['time_slot'] ?? '')=='4:30-6'?'selected':'' ?>>4:30-6</option>
<option value="6-7:30" <?= ($_GET['time_slot'] ?? '')=='6-7:30'?'selected':'' ?>>6-7:30</option>
</select>

<input type="date" name="from_date" class="form-control col" value="<?= $_GET['from_date'] ?? '' ?>">
<input type="date" name="to_date" class="form-control col" value="<?= $_GET['to_date'] ?? '' ?>">

<button name="search" class="btn btn-primary col">Filter</button>

</form>

<?php if($fees && $fees->num_rows > 0): ?>

<table class="table table-bordered">
<tr>
<th>GR</th>
<th>Name</th>
<th>Course</th>
<th>Month</th>
<th>Paid</th>
<th>Actions</th>
</tr>

<?php while($f=$fees->fetch_assoc()): 

$pendingMonths=[];
$admission=strtotime($f['admission_date']);
$current=strtotime(date('Y-m-01'));

while($admission <= $current){

$m=date('n',$admission);
$y=date('Y',$admission);

$check=$conn->query("
SELECT paid_fee,total_fee FROM fees
WHERE student_id='{$f['student_id']}'
AND course_id='{$f['course_id']}'
AND fee_month_num='$m'
AND fee_year='$y'
LIMIT 1
");

$row=$check->fetch_assoc();

if(!$row || $row['paid_fee'] < $row['total_fee']){
$pendingMonths[]=date("M Y",$admission);
}

$admission=strtotime("+1 month",$admission);
}

$pendingStr = !empty($pendingMonths) ? implode(", ",$pendingMonths) : "No Pending";
?>

<tr>
<td><?= $f['gr_no'] ?></td>
<td><?= $f['student_name'] ?></td>
<td><?= $f['course_name'] ?></td>
<td><?= date("F", mktime(0,0,0,$f['fee_month_num'],1)) ?></td>
<td><?= $f['paid_fee'] ?></td>

<td>

<!-- KEEP FILTER AFTER EDIT -->
<a href="edit_fee.php?id=<?= $f['fee_id'] ?>&<?= $queryString ?>" class="btn btn-primary btn-sm">Edit</a>

<button class="btn btn-danger btn-sm deleteBtn" data-id="<?= $f['fee_id'] ?>">Delete</button>

<button 
class="btn btn-success btn-sm printBtn"
data-name="<?= $f['student_name'] ?>"
data-father="<?= $f['father_name'] ?>"
data-gr="<?= $f['gr_no'] ?>"
data-course="<?= $f['course_name'] ?>"
data-month="<?= date("F", mktime(0,0,0,$f['fee_month_num'],1)) ?>"
data-year="<?= $f['fee_year'] ?>"
data-date="<?= $f['payment_date'] ?>"
data-total="<?= $f['total_fee'] ?>"
data-paid="<?= $f['paid_fee'] ?>"
data-pending="<?= $pendingStr ?>"
>
Print
</button>

</td>
</tr>

<?php endwhile; ?>

</table>

<?php endif; ?>

</div>
</div>

<!-- SLIP -->
<div class="slip-overlay" id="slipModal">
<div class="slip">

<div class="logo">
<img src="safcode_logo.png" width="80"><br>
<b>FEE VOUCHER</b>
</div>

<table class="table-slip">
<tr><td>Name</td><td id="s_name"></td></tr>
<tr><td>Father</td><td id="s_father"></td></tr>
<tr><td>GR</td><td id="s_gr"></td></tr>
<tr><td>Course</td><td id="s_course"></td></tr>
<tr><td>Month</td><td id="s_month"></td></tr>
<tr><td>Date</td><td id="s_date"></td></tr>
<tr><td>Total</td><td id="s_total"></td></tr>
<tr><td>Paid</td><td id="s_paid"></td></tr>
<tr><td>Pending</td><td id="s_pending"></td></tr>
</table>

<br>
Signature ________________________

<br><br>

<button onclick="window.print()" class="btn btn-dark btn-sm">Print</button>
<button onclick="$('#slipModal').hide()" class="btn btn-secondary btn-sm">Back</button>

</div>
</div>

<script>

$(document).on('click','.deleteBtn',function(){
if(confirm("Delete record?")){
let id=$(this).data('id');
$.post('ajax_fee.php',{delete_id:id},function(){
location.reload();
});
}
});

$(document).on('click','.printBtn',function(){

$('#s_name').text($(this).data('name'));
$('#s_father').text($(this).data('father'));
$('#s_gr').text($(this).data('gr'));
$('#s_course').text($(this).data('course'));
$('#s_month').text($(this).data('month')+" "+$(this).data('year'));
$('#s_date').text($(this).data('date'));
$('#s_total').text($(this).data('total'));
$('#s_paid').text($(this).data('paid'));
$('#s_pending').text($(this).data('pending'));

$('#slipModal').css('display','flex');

});

</script>

<?php include("../includes/footer.php"); ?>

</body>
</html>