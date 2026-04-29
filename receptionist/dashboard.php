<?php
include("../config/init.php");
checkRole(['receptionist']);

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

// ✅ LOGGER (FIXED)
logActivity( $user_id, $role, "Opened Receptionist Dashboard");

// ================= STATS =================

// Total Students
$res = $conn->query("SELECT COUNT(*) as t FROM students");
$total_students = $res->fetch_assoc()['t'] ?? 0;

// Total Fee
$res = $conn->query("SELECT SUM(total_fee) as t FROM fees");
$total_fee = $res->fetch_assoc()['t'] ?? 0;

// Paid Fee
$res = $conn->query("SELECT SUM(paid_fee) as t FROM fees");
$paid = $res->fetch_assoc()['t'] ?? 0;

// ✅ Safe Pending
$total_fee = $total_fee ?: 0;
$paid      = $paid ?: 0;
$pending   = $total_fee - $paid;
?>

<!DOCTYPE html>
<html>
<head>
<title>Receptionist Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">

<style>
.card-box{
    border-radius: 10px;
    transition: 0.3s;
}
.card-box:hover{
    transform: scale(1.03);
}
</style>

</head>

<body>

<div class="d-flex">

<?php include("../includes/sidebar.php"); ?>

<div class="content flex-grow-1 p-4">

<h3 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['name']) ?> 👋</h3>

<!-- ================= STATS ================= -->
<div class="row g-3">

<div class="col-md-3">
<div class="card card-box p-3 bg-primary text-white shadow-sm">
<h6>Total Students</h6>
<h2><?= $total_students ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card card-box p-3 bg-success text-white shadow-sm">
<h6>Total Fee</h6>
<h2><?= number_format($total_fee) ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card card-box p-3 bg-info text-white shadow-sm">
<h6>Collected</h6>
<h2><?= number_format($paid) ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card card-box p-3 bg-danger text-white shadow-sm">
<h6>Pending</h6>
<h2><?= number_format($pending) ?></h2>
</div>
</div>

</div>

<hr class="my-4">

<!-- ================= QUICK ACTIONS ================= -->
<h5 class="mb-3">Quick Actions</h5>

<div class="d-flex flex-wrap gap-2">

<a href="add_student.php" class="btn btn-primary">➕ Add Student</a>

<a href="view_students.php" class="btn btn-secondary">📋 View Students</a>

<a href="fee_submit.php" class="btn btn-success">💰 Submit Fee</a>

<a href="view_fee.php" class="btn btn-info text-white">📊 View Fee</a>

</div>

</div>
</div>

<?php include("../includes/footer.php"); ?>

</body>
</html>