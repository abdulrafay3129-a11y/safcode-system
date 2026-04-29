<?php
include("../config/init.php");
checkRole(['admin']);

/* ================= NUMBER TO WORDS FUNCTION ================= */
function numberToWords($number)
{
    $hyphen = '-';
    $dictionary = [
        0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four',
        5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
        10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen',
        14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
        18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty',
        30 => 'thirty', 40 => 'forty', 50 => 'fifty', 60 => 'sixty',
        70 => 'seventy', 80 => 'eighty', 90 => 'ninety',
        100 => 'hundred', 1000 => 'thousand', 100000 => 'lakh', 10000000 => 'crore'
    ];

    if (!is_numeric($number)) return '';

    if ($number < 21) return $dictionary[$number];

    if ($number < 100) {
        $tens = ((int)($number / 10)) * 10;
        $units = $number % 10;
        return $units ? $dictionary[$tens] . $hyphen . $dictionary[$units] : $dictionary[$tens];
    }

    if ($number < 1000) {
        $hundreds = (int)($number / 100);
        $remainder = $number % 100;
        return $dictionary[$hundreds] . ' hundred' . ($remainder ? ' ' . numberToWords($remainder) : '');
    }

    if ($number < 100000) {
        $thousands = (int)($number / 1000);
        $remainder = $number % 1000;
        return numberToWords($thousands) . ' thousand' . ($remainder ? ' ' . numberToWords($remainder) : '');
    }

    return (string)$number;
}

// LOGGER
logActivity($_SESSION['user_id'], $_SESSION['role'], "Viewed Total Fee");

// COURSES
$courses = $conn->query("SELECT id,name FROM courses WHERE status=1");

// FILTER
$where = "WHERE s.status=1";
$params = [];
$types = "";

if(isset($_GET['search'])){
    if(!empty($_GET['course_id'])){
        $where .= " AND sch.course_id=?";
        $types.="i";
        $params[]=$_GET['course_id'];
    }

    if(!empty($_GET['days'])){
        $where .= " AND sch.days=?";
        $types.="s";
        $params[]=$_GET['days'];
    }

    if(!empty($_GET['time_slot'])){
        $where .= " AND sch.time_slot=?";
        $types.="s";
        $params[]=$_GET['time_slot'];
    }

    if(!empty($_GET['name'])){
        $where .= " AND s.name LIKE ?";
        $types.="s";
        $params[]="%".$_GET['name']."%";
    }

    if(!empty($_GET['gr_no'])){
        $where .= " AND s.gr_no LIKE ?";
        $types.="s";
        $params[]="%".$_GET['gr_no']."%";
    }

    if(!empty($_GET['from_date'])){
        $where .= " AND sch.admission_date>=?";
        $types.="s";
        $params[]=$_GET['from_date'];
    }

    if(!empty($_GET['to_date'])){
        $where .= " AND sch.admission_date<=?";
        $types.="s";
        $params[]=$_GET['to_date'];
    }
}

// TOTAL
$sql_total = "SELECT COUNT(DISTINCT s.id) total_students
FROM students s
JOIN student_course_history sch ON s.id=sch.student_id
WHERE 1=1";

if($where){
    $sql_total .= " AND ".str_replace("WHERE ","",$where);
}

$stmt = $conn->prepare($sql_total);
if($params) $stmt->bind_param($types,...$params);
$stmt->execute();
$total_students = $stmt->get_result()->fetch_assoc()['total_students'] ?? 0;

// STUDENTS
$sql_students = "
SELECT 
s.id,
s.name,
s.father_name,
s.gr_no,
c.name course_name,
c.fees AS course_fee,
sch.days,
sch.time_slot
FROM students s
JOIN student_course_history sch ON s.id=sch.student_id
JOIN courses c ON sch.course_id=c.id
INNER JOIN (
    SELECT student_id, MAX(id) latest
    FROM student_course_history
    GROUP BY student_id
) latest_sch ON sch.id=latest_sch.latest
";

if($where){
    $sql_students .= " ".$where;
}

$sql_students .= " ORDER BY sch.id DESC";

$stmt2 = $conn->prepare($sql_students);
if($params) $stmt2->bind_param($types,...$params);
$stmt2->execute();
$result_students = $stmt2->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Total Fee Voucher</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.slip-container{display:none;}

@media print{
body *{visibility:hidden;}

.slip-container, .slip-container *{
visibility:visible;
}

.slip-container{
position:absolute;
top:0;
left:0;
width:100%;
display:flex;
flex-wrap:wrap;
}

.slip{
width:48%;
border:2px solid #000;
margin:1%;
padding:14px;
background:#fff;
font-size:13px;
}

.logo{
text-align:center;
margin-bottom:6px;
}

.logo img{
max-width:180px;
display:block;
margin:auto;
}

.slip-title{
text-align:center;
font-weight:bold;
margin-bottom:10px;
}

.table-slip{
width:100%;
border-collapse:collapse;
margin-top:10px;
}

.table-slip td{
border:1px solid #000;
padding:6px;
}

.stamp-box{
margin-top:15px;
border:1px dashed #000;
height:55px;
text-align:center;
padding-top:18px;
font-size:12px;
}

.signature-inline{
margin-top:15px;
font-weight:600;
}

.sig-line{
display:inline-block;
border-bottom:1px solid #000;
width:180px;
margin-left:10px;
transform: translateY(-3px);
}
}
</style>

</head>

<body>

<div class="d-flex">
<?php include("../includes/sidebar.php"); ?>

<div class="content p-4 flex-grow-1">

<h3>📊 Total Students</h3>

<form method="GET" class="row g-2 mb-4">

<div class="col-md-2">
<select name="course_id" class="form-select">
<option value="">Course</option>
<?php while($c=$courses->fetch_assoc()): ?>
<option value="<?= $c['id']?>"><?= $c['name']?></option>
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

<div class="col-md-2">
<input type="text" name="name" class="form-control" placeholder="Name">
</div>

<div class="col-md-2">
<input type="text" name="gr_no" class="form-control" placeholder="GR No">
</div>

<div class="col-md-2">
<button class="btn btn-primary w-100" name="search">Filter</button>
</div>

</form>

<div class="d-flex justify-content-between align-items-center mb-3">
<div class="alert alert-success mb-0">
Total Students: <?= $total_students ?>
</div>

<a href="pending_fee.php" class="btn btn-danger">
Pending Fee
</a>
</div>

<button onclick="window.print()" class="btn btn-success mb-3">
Print Vouchers
</button>

<table class="table table-bordered">
<thead>
<tr>
<th>ID</th>
<th>Name</th>
<th>Father</th>
<th>GR</th>
<th>Course</th>
<th>Fees</th>
<th>Days</th>
<th>Time</th>
</tr>
</thead>

<tbody>
<?php while($s=$result_students->fetch_assoc()): ?>
<tr>
<td><?= $s['id']?></td>
<td><?= htmlspecialchars($s['name'])?></td>
<td><?= htmlspecialchars($s['father_name'])?></td>
<td><?= $s['gr_no']?></td>
<td><?= $s['course_name']?></td>
<td><?= $s['course_fee']?></td>
<td><?= $s['days']?></td>
<td><?= $s['time_slot']?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<!-- VOUCHERS -->
<div class="slip-container">

<?php $result_students->data_seek(0); while($s=$result_students->fetch_assoc()): ?>

<div class="slip">
<div class="logo">
<img src="safcode.png">
</div>

<div class="slip-title">FEE VOUCHER - STUDENT COPY</div>

<table class="table-slip">
<tr><td>Name</td><td><?= ucfirst($s['name']) ?></td></tr>
<tr><td>Father Name</td><td><?= ucfirst($s['father_name']) ?></td></tr>
<tr><td>GR No</td><td><?= $s['gr_no'] ?></td></tr>
<tr><td>Course</td><td><?= $s['course_name'] ?></td></tr>
<tr><td>Fees</td><td><?= $s['course_fee'] ?></td></tr>
<tr><td>Fee Status</td><td>Unpaid</td></tr>
<tr><td>Fee in Words</td><td><?= ucfirst(numberToWords($s['course_fee'])) ?> only</td></tr>
</table>

<div class="stamp-box">STAMP AREA</div>

<div class="signature-inline">
Signature <span class="sig-line"></span>
</div>
</div>

<div class="slip">
<div class="logo">
<img src="safcode.png">
</div>

<div class="slip-title">FEE VOUCHER - INSTITUTE COPY</div>

<table class="table-slip">
<tr><td>Name</td><td><?= ucfirst($s['name']) ?></td></tr>
<tr><td>Father Name</td><td><?= ucfirst($s['father_name']) ?></td></tr>
<tr><td>GR No</td><td><?= $s['gr_no'] ?></td></tr>
<tr><td>Course</td><td><?= $s['course_name'] ?></td></tr>
<tr><td>Fees</td><td><?= $s['course_fee'] ?></td></tr>
<tr><td>Fee Status</td><td>Unpaid</td></tr>
<tr><td>Fee in Words</td><td><?= ucfirst(numberToWords($s['course_fee'])) ?> only</td></tr>
</table>

<div class="stamp-box">STAMP AREA</div>

<div class="signature-inline">
Signature <span class="sig-line"></span>
</div>
</div>

<?php endwhile; ?>

</div>

</div>
</div>

<?php include("../includes/footer.php"); ?>

</body>
</html>