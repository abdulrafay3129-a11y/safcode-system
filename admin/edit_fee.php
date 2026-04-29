<?php
include("../config/init.php");
checkRole(['admin']);

if(!isset($_GET['id'])){
    die("Fee ID missing");
}

$fee_id = (int)$_GET['id'];

$stmt = $conn->prepare("
SELECT f.*, s.name AS student_name, s.gr_no
FROM fees f
JOIN students s ON f.student_id = s.id
WHERE f.id=?
");
$stmt->bind_param("i", $fee_id);
$stmt->execute();
$fee = $stmt->get_result()->fetch_assoc();

if(!$fee){
    die("Record not found");
}

/* UPDATE */
if(isset($_POST['update_fee'])){

    $fee_type = $_POST['fee_type'];

    $total_fee = (float)$_POST['total_fee'];
    $paid_fee  = (float)$_POST['paid_fee'];
    $discount  = (float)$_POST['discount'];

    $payment_date = $_POST['payment_date'];
    if(empty($payment_date) || !strtotime($payment_date)){
        $payment_date = date('Y-m-d');
    }

    $fee_month_num = (int)$_POST['fee_month_num'];
    $fee_year = (int)$_POST['fee_year'];

    $remarks = $_POST['remarks'];

    $update = $conn->prepare("
        UPDATE fees SET 
            fee_type=?,
            total_fee=?,
            paid_fee=?,
            discount=?,
            payment_date=?,
            fee_month_num=?,
            fee_year=?,
            remarks=?
        WHERE id=?
    ");

    $update->bind_param(
        "sdddsiisi",
        $fee_type,
        $total_fee,
        $paid_fee,
        $discount,
        $payment_date,
        $fee_month_num,
        $fee_year,
        $remarks,
        $fee_id
    );

    $update->execute();

    header("Location: edit_fee.php?id=".$fee_id."&success=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Fee</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.pending-box{
    background:#f8f9fa;
    padding:10px;
    border-radius:5px;
    font-weight:bold;
}
</style>

<script>
function calculatePending(){
    let total = parseFloat(document.getElementById('total_fee').value) || 0;
    let paid  = parseFloat(document.getElementById('paid_fee').value) || 0;
    let discount = parseFloat(document.getElementById('discount').value) || 0;

    let final = total - discount;
    let pending = final - paid;

    if(pending < 0) pending = 0;

    document.getElementById('pending').innerText = pending.toFixed(2);
}
</script>

</head>

<body>

<div class="d-flex">

<?php include("../includes/sidebar.php"); ?>

<div class="content p-4 w-100">

<h3>✏️ Edit Fee</h3>

<?php if(isset($_GET['success'])): ?>
<div class="alert alert-success">Fee Updated Successfully</div>
<?php endif; ?>

<div class="card p-4">

<form method="POST" class="row g-3">

<!-- STUDENT -->
<div class="col-md-3">
<label>Student Name</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($fee['student_name']) ?>" readonly>
</div>

<div class="col-md-3">
<label>GR No</label>
<input type="text" class="form-control" value="<?= $fee['gr_no'] ?>" readonly>
</div>

<!-- TYPE -->
<div class="col-md-3">
<label>Fee Type</label>
<select name="fee_type" class="form-select">
<option value="monthly" <?= ($fee['fee_type']=='monthly')?'selected':'' ?>>Monthly</option>
<option value="installment" <?= ($fee['fee_type']=='installment')?'selected':'' ?>>Installment</option>
</select>
</div>

<!-- TOTAL -->
<div class="col-md-3">
<label>Total Fee</label>
<input type="number" id="total_fee" step="0.01" name="total_fee" class="form-control" value="<?= $fee['total_fee'] ?>" oninput="calculatePending()">
</div>

<!-- PAID -->
<div class="col-md-3">
<label>Paid Fee</label>
<input type="number" id="paid_fee" step="0.01" name="paid_fee" class="form-control" value="<?= $fee['paid_fee'] ?>" oninput="calculatePending()">
</div>

<!-- DISCOUNT -->
<div class="col-md-3">
<label>Discount</label>
<input type="number" id="discount" step="0.01" name="discount" class="form-control" value="<?= $fee['discount'] ?>" oninput="calculatePending()">
</div>

<!-- 🔥 PENDING LIVE -->
<div class="col-md-3">
<label>Pending</label>
<div class="pending-box" id="pending">
<?= max(0, ($fee['total_fee'] - $fee['discount']) - $fee['paid_fee']) ?>
</div>
</div>

<!-- DATE -->
<div class="col-md-3">
<label>Payment Date</label>
<input type="date" name="payment_date" class="form-control"
value="<?= (!empty($fee['payment_date']) && $fee['payment_date']!='0000-00-00') ? $fee['payment_date'] : date('Y-m-d') ?>">
</div>

<!-- MONTH -->
<div class="col-md-3">
<label>Fee Month</label>
<select name="fee_month_num" class="form-select" required>
<?php for($m=1;$m<=12;$m++): ?>
<option value="<?= $m ?>" <?= ($fee['fee_month_num']==$m)?'selected':'' ?>>
<?= date("F", mktime(0,0,0,$m,1)) ?>
</option>
<?php endfor; ?>
</select>
</div>

<!-- YEAR -->
<div class="col-md-3">
<label>Fee Year</label>
<input type="number" name="fee_year" class="form-control" value="<?= $fee['fee_year'] ?>">
</div>

<!-- REMARKS -->
<div class="col-md-6">
<label>Remarks</label>
<input type="text" name="remarks" class="form-control" value="<?= htmlspecialchars($fee['remarks']) ?>">
</div>

<!-- BUTTON -->
<div class="col-md-12 mt-3">
<button type="submit" name="update_fee" class="btn btn-primary">Update Fee</button>
<a href="view_fee.php" class="btn btn-secondary">Back</a>
</div>

</form>

</div>

</div>
</div>

<?php include("../includes/footer.php"); ?>

<script>
calculatePending();
</script>

</body>
</html>