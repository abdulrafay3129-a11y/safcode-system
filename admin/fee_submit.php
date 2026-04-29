<?php
include("../config/init.php");
checkRole(['admin']);

$students = [];
$success = "";
$slipData = [];

/* ================= NUMBER TO WORDS ================= */
function numberToWords($number){
    $dictionary = [
        0=>'zero',1=>'one',2=>'two',3=>'three',4=>'four',5=>'five',
        6=>'six',7=>'seven',8=>'eight',9=>'nine',10=>'ten',
        11=>'eleven',12=>'twelve',13=>'thirteen',14=>'fourteen',
        15=>'fifteen',16=>'sixteen',17=>'seventeen',18=>'eighteen',
        19=>'nineteen',20=>'twenty',30=>'thirty',40=>'forty',
        50=>'fifty',60=>'sixty',70=>'seventy',80=>'eighty',90=>'ninety'
    ];

    if($number<21) return $dictionary[$number];
    if($number<100){
        $tens=(int)($number/10)*10;
        $unit=$number%10;
        return $unit ? $dictionary[$tens]."-".$dictionary[$unit] : $dictionary[$tens];
    }
    if($number<1000){
        return $dictionary[(int)($number/100)]." hundred ".numberToWords($number%100);
    }
    return $number;
}

/* COURSES */
$courses = $conn->query("SELECT id,name FROM courses WHERE status=1");

/* ================= SEARCH ================= */
if(isset($_GET['search'])){

    $sql = "SELECT 
        s.id,s.gr_no,s.name,s.father_name,
        sch.course_id,sch.days,sch.time_slot,
        sch.admission_date,
        c.fees AS course_fee,c.name AS course_name,
        c.duration_months
    FROM students s
    JOIN student_course_history sch ON sch.student_id=s.id
    JOIN courses c ON c.id=sch.course_id
    WHERE s.status='active'";

    $params=[]; $types="";

    if(!empty($_GET['gr_no'])){
        $sql.=" AND s.gr_no LIKE ?";
        $types.="s"; $params[]="%".$_GET['gr_no']."%";
    }

    if(!empty($_GET['name'])){
        $sql.=" AND s.name LIKE ?";
        $types.="s"; $params[]="%".$_GET['name']."%";
    }

    if(!empty($_GET['course_id'])){
        $sql.=" AND sch.course_id=?";
        $types.="i"; $params[]=$_GET['course_id'];
    }

    if(!empty($_GET['days'])){
        $sql.=" AND sch.days=?";
        $types.="s"; $params[]=$_GET['days'];
    }

    if(!empty($_GET['time_slot'])){
        $sql.=" AND sch.time_slot=?";
        $types.="s"; $params[]=$_GET['time_slot'];
    }

    $stmt=$conn->prepare($sql);
    if($params) $stmt->bind_param($types,...$params);
    $stmt->execute();
    $students=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ================= SAVE ================= */
if(isset($_POST['save_fee'])){

    $selected=$_POST['row_key'] ?? [];
    if(empty($selected)) die("Select student");

    $payment_date=$_POST['payment_date'];
    $admin_id=$_SESSION['user_id'];
    $fee_month_num=(int)$_POST['fee_month_num'];
    $fee_year=(int)$_POST['fee_year'];

    foreach($selected as $key){

        $student_id=$_POST['student_id'][$key];
        $course_id=$_POST['course_id'][$key];
        $course_fee=$_POST['course_fee'][$key];
        $pay_amount=$_POST['pay_amount'][$key];

        /* DELETE SAME MONTH */
        $conn->query("
            DELETE FROM fees 
            WHERE student_id='$student_id'
            AND course_id='$course_id'
            AND fee_month_num='$fee_month_num'
            AND fee_year='$fee_year'
        ");

        /* INSERT */
        $insert=$conn->prepare("
            INSERT INTO fees
            (student_id,course_id,fee_type,fee_month_num,fee_year,total_fee,paid_fee,payment_date,created_by)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");

        $type='monthly';

        $insert->bind_param("iiisiiisi",
            $student_id,$course_id,$type,$fee_month_num,
            $fee_year,$course_fee,$pay_amount,$payment_date,$admin_id
        );

        $insert->execute();

        /* ===== PENDING CALCULATION ===== */
        $pendingMonths = [];

        $admission = strtotime($_POST['admission_date'][$key]);
        $current   = strtotime(date('Y-m-01'));

        while($admission <= $current){

            $m = date('n',$admission);
            $y = date('Y',$admission);

            $check = $conn->query("
                SELECT paid_fee,total_fee 
                FROM fees 
                WHERE student_id='$student_id'
                AND course_id='$course_id'
                AND fee_month_num='$m'
                AND fee_year='$y'
                LIMIT 1
            ");

            $row = $check->fetch_assoc();

            if(!$row || $row['paid_fee'] < $row['total_fee']){
                $pendingMonths[] = date("M Y",$admission);
            }

            $admission = strtotime("+1 month",$admission);
        }

        /* SLIP */
        if(empty($slipData)){
            $slipData=[
                "name"=>ucwords($_POST['student_name'][$key]),
                "father"=>ucwords($_POST['father_name'][$key]),
                "gr"=>$_POST['gr_no'][$key],
                "course"=>$_POST['course_name'][$key],
                "fee"=>$course_fee,
                "paid"=>$pay_amount,
                "words"=>ucfirst(numberToWords($course_fee)),
                "month"=>date("F", mktime(0,0,0,$fee_month_num,1)),
                "year"=>$fee_year,
                "date"=>$payment_date,
                "pending"=>$pendingMonths
            ];
        }
    }

    $success="Fee Saved Successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Fee Submit</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.slip-box{display:none;}

@media print{
body *{visibility:hidden;}

.slip-box,.slip-box *{visibility:visible;}

.slip-box{
position:absolute;
top:0;left:0;width:100%;
display:flex;justify-content:center;
}

.slip{
width:48%;
border:2px solid #000;
padding:14px;
background:#fff;
}

.logo{text-align:center;margin-bottom:6px;}
.logo img{max-width:150px;}

.table-slip{
width:100%;
border-collapse:collapse;
margin-top:10px;
}
.table-slip td{
border:1px solid #000;
padding:6px;
}
}
</style>

<script>
function printSlip(){
document.querySelector('.slip-box').style.display='block';
window.print();
}
</script>

</head>

<body>

<div class="d-flex">
<?php include("../includes/sidebar.php"); ?>

<div class="content p-4 w-100">

<h4>Fee Submit</h4>

<?php if($success): ?>
<div class="alert alert-success"><?= $success ?></div>
<button onclick="printSlip()" class="btn btn-dark mb-3">Print Voucher</button>
<?php endif; ?>

<!-- FILTER -->
<<!-- FILTER -->
<form method="GET" class="row g-2 mb-3">

<input name="gr_no" class="form-control col"
placeholder="GR No"
value="<?= $_GET['gr_no'] ?? '' ?>">

<input name="name" class="form-control col"
placeholder="Name"
value="<?= $_GET['name'] ?? '' ?>">

<select name="course_id" class="form-select col">
<option value="">Course</option>
<?php 
$courses2 = $conn->query("SELECT id,name FROM courses WHERE status=1");
while($c=$courses2->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>"
<?= (($_GET['course_id'] ?? '')==$c['id'])?'selected':'' ?>>
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>

<!-- DAYS FILTER (VIEW STUDENTS JESA) -->
<select name="days" class="form-select col">
<option value="">Days</option>
<option value="MonWedFri" <?= ($_GET['days'] ?? '')=='MonWedFri'?'selected':'' ?>>MonWedFri</option>
<option value="TueThuSat" <?= ($_GET['days'] ?? '')=='TueThuSat'?'selected':'' ?>>TueThuSat</option>
</select>

<!-- TIME FILTER -->
<select name="time_slot" class="form-select col">
<option value="">Time</option>
<option value="3-4:30" <?= ($_GET['time_slot'] ?? '')=='3-4:30'?'selected':'' ?>>3-4:30</option>
<option value="4:30-6" <?= ($_GET['time_slot'] ?? '')=='4:30-6'?'selected':'' ?>>4:30-6</option>
<option value="6-7:30" <?= ($_GET['time_slot'] ?? '')=='6-7:30'?'selected':'' ?>>6-7:30</option>
</select>

<button class="btn btn-primary col" name="search">Search</button>

<a href="fee_submit.php" class="btn btn-secondary col">Reset</a>

</form>

<?php if($students): ?>
<form method="POST">

<table class="table table-bordered">
<tr>
<th>Select</th><th>GR</th><th>Name</th><th>Course</th><th>Fee</th><th>Pay</th>
</tr>

<?php foreach($students as $i=>$s): ?>
<tr>
<td><input type="checkbox" name="row_key[]" value="<?= $i ?>"></td>
<td><?= $s['gr_no'] ?></td>
<td><?= $s['name'] ?></td>
<td><?= $s['course_name'] ?></td>

<td><input name="course_fee[<?= $i ?>]" value="<?= $s['course_fee'] ?>"></td>
<td><input name="pay_amount[<?= $i ?>]" value="<?= $s['course_fee'] ?>"></td>

<input type="hidden" name="student_id[<?= $i ?>]" value="<?= $s['id'] ?>">
<input type="hidden" name="course_id[<?= $i ?>]" value="<?= $s['course_id'] ?>">
<input type="hidden" name="student_name[<?= $i ?>]" value="<?= $s['name'] ?>">
<input type="hidden" name="father_name[<?= $i ?>]" value="<?= $s['father_name'] ?>">
<input type="hidden" name="gr_no[<?= $i ?>]" value="<?= $s['gr_no'] ?>">
<input type="hidden" name="course_name[<?= $i ?>]" value="<?= $s['course_name'] ?>">
<input type="hidden" name="admission_date[<?= $i ?>]" value="<?= $s['admission_date'] ?>">
</tr>
<?php endforeach; ?>
</table>

<select name="fee_month_num" required class="form-control mb-2">
<option value="">Select Month</option>
<?php for($m=1;$m<=12;$m++): ?>
<option value="<?= $m ?>"><?= date("F", mktime(0,0,0,$m,1)) ?></option>
<?php endfor; ?>
</select>

<select name="fee_year" class="form-control mb-2" required>
<?php 
$currentYear = date('Y');
for($y = $currentYear-2; $y <= $currentYear+2; $y++): ?>
<option value="<?= $y ?>" <?= $y==$currentYear?'selected':'' ?>>
<?= $y ?>
</option>
<?php endfor; ?>
</select>
<input type="date" name="payment_date" class="form-control mb-2" value="<?= date('Y-m-d') ?>" required>

<button name="save_fee" class="btn btn-success">Submit Fee</button>

</form>
<?php endif; ?>

</div>
</div>

<!-- PRINT SLIP -->
<div class="slip-box">
<?php if(!empty($slipData)): ?>
<div class="slip">

<div class="logo">
<img src="safcode.png">
</div>

<h5 class="text-center">FEE VOUCHER</h5>

<table class="table-slip">
<tr><td>Name</td><td><?= $slipData['name'] ?></td></tr>
<tr><td>Father</td><td><?= $slipData['father'] ?></td></tr>
<tr><td>GR No</td><td><?= $slipData['gr'] ?></td></tr>
<tr><td>Course</td><td><?= $slipData['course'] ?></td></tr>

<tr><td>Month Paid</td><td><?= $slipData['month']." ".$slipData['year'] ?></td></tr>
<tr><td>Payment Date</td><td><?= $slipData['date'] ?></td></tr>

<tr><td>Total Fee</td><td><?= $slipData['fee'] ?></td></tr>
<tr><td>Paid</td><td><?= $slipData['paid'] ?></td></tr>

<tr><td>Fee in Words</td><td><?= $slipData['words'] ?> only</td></tr>

<tr>
<td>Pending Months</td>
<td style="color:red;">
<?= !empty($slipData['pending']) ? implode(", ", $slipData['pending']) : "No Pending" ?>
</td>
</tr>

</table>

<br>
Signature ____________________

</div>
<?php endif; ?>
</div>

<?php include("../includes/footer.php"); ?>

</body>
</html>