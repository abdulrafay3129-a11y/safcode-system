<?php
include("../config/init.php"); // ✅ db + session + auth + logger
checkRole(['admin']);

if(!isset($_GET['id'])){
    die("❌ Fee ID missing");
}

$fee_id = intval($_GET['id']);

/* ================= FETCH EXISTING FEE ================= */
$stmt = $conn->prepare("
    SELECT f.*, s.name AS student_name, c.name AS course_name
    FROM fees f
    JOIN students s ON f.student_id=s.id
    LEFT JOIN student_course_history sch ON sch.student_id=s.id
    LEFT JOIN courses c ON sch.course_id=c.id
    WHERE f.id=?
    ORDER BY sch.id DESC
    LIMIT 1
");
$stmt->bind_param("i", $fee_id);
$stmt->execute();
$fee = $stmt->get_result()->fetch_assoc();

if(!$fee){
    die("❌ Fee record not found");
}

/* ================= UPDATE LOGIC ================= */
if(isset($_POST['update_fee'])){

    $fee_type     = $_POST['fee_type'] ?? $fee['fee_type'];
    $total_fee    = $_POST['total_fee'] ?? $fee['total_fee'];
    $paid_fee     = $_POST['paid_fee'] ?? $fee['paid_fee'];
    $discount     = $_POST['discount'] ?? $fee['discount'];
    $payment_date = $_POST['payment_date'] ?? $fee['payment_date'];
    $remarks      = $_POST['remarks'] ?? $fee['remarks'];
    $admin_id     = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        UPDATE fees
        SET fee_type=?, total_fee=?, paid_fee=?, discount=?, payment_date=?, remarks=?, created_by=?
        WHERE id=?
    ");
    $stmt->bind_param(
        "sdddssii",
        $fee_type,
        $total_fee,
        $paid_fee,
        $discount,
        $payment_date,
        $remarks,
        $admin_id,
        $fee_id
    );

    if($stmt->execute()){
        logActivity( $admin_id, $_SESSION['role'], "Fee updated | Fee ID: $fee_id | Student: {$fee['student_name']}");
        $success = "✅ Fee updated successfully!";
        // Reload updated data
        header("Location: edit_fee.php?id=$fee_id&success=1");
        exit;
    } else {
        $error = "❌ Failed to update fee.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Fee | Safcode IMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="d-flex">
<?php include("../includes/sidebar.php"); ?>

<div class="content p-4 flex-grow-1">
<h3>✏️ Edit Fee for <?= htmlspecialchars($fee['student_name']) ?> (<?= htmlspecialchars($fee['course_name']) ?>)</h3>

<?php if(isset($success)): ?>
<div class="alert alert-success"><?= $success ?></div>
<?php elseif(isset($error)): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form method="POST" class="row g-3">

<div class="col-md-3">
<label>Fee Type</label>
<select name="fee_type" class="form-select">
<option value="admission" <?= ($fee['fee_type']=='admission')?'selected':'' ?>>Admission</option>
<option value="monthly" <?= ($fee['fee_type']=='monthly')?'selected':'' ?>>Monthly</option>
<option value="certificate" <?= ($fee['fee_type']=='certificate')?'selected':'' ?>>Certificate</option>
</select>
</div>

<div class="col-md-3">
<label>Total Fee</label>
<input type="number" name="total_fee" class="form-control" value="<?= $fee['total_fee'] ?>">
</div>

<div class="col-md-3">
<label>Paid Fee</label>
<input type="number" name="paid_fee" class="form-control" value="<?= $fee['paid_fee'] ?>">
</div>

<div class="col-md-3">
<label>Discount</label>
<input type="number" name="discount" class="form-control" value="<?= $fee['discount'] ?>">
</div>

<div class="col-md-3">
<label>Payment Date</label>
<input type="date" name="payment_date" class="form-control" value="<?= $fee['payment_date'] ?>">
</div>

<div class="col-md-6">
<label>Remarks</label>
<input type="text" name="remarks" class="form-control" value="<?= htmlspecialchars($fee['remarks']) ?>">
</div>

<div class="col-md-12">
<button type="submit" name="update_fee" class="btn btn-primary mt-3">💾 Update Fee</button>
<a href="view_fees.php" class="btn btn-secondary mt-3">Back</a>
</div>

</form>
</div>
</div>

<?php include("../includes/footer.php"); ?>
</body>
</html>