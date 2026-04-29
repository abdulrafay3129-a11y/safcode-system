<?php
include("../config/init.php");
checkRole(['receptionist']);

$id = intval($_GET['id']);
$admin_id = $_SESSION['user_id'];

$message = "";

/* STUDENT */
$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if(!$student){
    die("Student not found");
}

/* COURSES */
$courses = $conn->query("SELECT * FROM courses WHERE status=1");

/* ADD COURSE */
if(isset($_POST['add_course'])){

    $course_id      = $_POST['course_id'];
    $course_fee     = $_POST['course_fee'];
    $discount       = $_POST['discount'] ?? 0;
    $days           = $_POST['days'];
    $time_slot      = $_POST['time_slot'];
    $admission_date = $_POST['admission_date'];
    $created_at     = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("
        INSERT INTO student_course_history
        (student_id, course_id, course_fee, admission_date, discount, time_slot, days, created_by, created_at)
        VALUES (?,?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param("iidssssis",
        $id,
        $course_id,
        $course_fee,
        $admission_date,
        $discount,
        $time_slot,
        $days,
        $admin_id,
        $created_at
    );

    if($stmt->execute()){
        logActivity($admin_id,$_SESSION['role'],"Added new course for student ID: $id");

        $message = "✅ Course added successfully!";
    } else {
        $message = "❌ Error: ".$conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Course</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f5f6fa;
}
.card{
    border-radius:12px;
}
</style>
</head>

<body>

<div class="d-flex">
<?php include("../includes/sidebar.php"); ?>

<div class="content p-4 flex-grow-1">

<div class="card shadow p-4">

<h4 class="mb-3">➕ Add Another Course</h4>

<!-- MESSAGE -->
<?php if($message): ?>
<div class="alert alert-info"><?= $message ?></div>
<?php endif; ?>

<form method="POST" class="row g-3">

<!-- STUDENT -->
<div class="col-md-6">
<label>Student Name</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($student['name']) ?>" readonly>
</div>

<!-- COURSE -->
<div class="col-md-4">
<label>Course</label>
<select name="course_id" id="courseSelect" class="form-select" required>
<option value="">Select Course</option>
<?php while($c=$courses->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>" data-fee="<?= $c['fees'] ?>">
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<!-- FEE -->
<div class="col-md-4">
<label>Course Fee</label>
<input type="text" id="feeBox" class="form-control" readonly>
<input type="hidden" name="course_fee" id="feeInput">
</div>

<!-- DISCOUNT -->
<div class="col-md-3">
<label>Discount</label>
<input type="number" name="discount" class="form-control" value="0">
</div>

<!-- DAYS -->
<div class="col-md-3">
<label>Days</label>
<select name="days" class="form-select" required>
<option value="">Select</option>
<option value="MonWedFri">Mon Wed Fri</option>
<option value="TueThuSat">Tue Thu Sat</option>
</select>
</div>

<!-- TIME -->
<div class="col-md-3">
<label>Time Slot</label>
<select name="time_slot" class="form-select" required>
<option value="">Select</option>
<option value="3-4:30">3:00 - 4:30</option>
<option value="4:30-6">4:30 - 6:00</option>
<option value="6-7:30">6:00 - 7:30</option>
</select>
</div>

<!-- DATE -->
<div class="col-md-3">
<label>Admission Date</label>
<input type="date" name="admission_date" class="form-control" value="<?= date('Y-m-d') ?>">
</div>

<!-- BUTTONS -->
<div class="col-12 mt-3">
<button class="btn btn-dark" name="add_course">Add Course</button>

<a href="view_students.php" class="btn btn-secondary">Back</a>
</div>

</form>

</div>

</div>
</div>

<script>
const select = document.getElementById('courseSelect');
const feeBox = document.getElementById('feeBox');
const feeInput = document.getElementById('feeInput');

select.addEventListener('change', ()=>{
    let fee = select.options[select.selectedIndex].getAttribute('data-fee');
    feeBox.value = fee;
    feeInput.value = fee;
});
</script>

<?php include("../includes/footer.php"); ?>

</body>
</html>