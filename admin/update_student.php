<?php
include("../config/init.php");
checkRole(['admin']);

$id = intval($_GET['id']);
$admin_id = $_SESSION['user_id'];

/* ================= FETCH STUDENT ================= */
$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if(!$student){
    die("❌ Student not found");
}

/* ================= FETCH LAST HISTORY ================= */
$stmt2 = $conn->prepare("SELECT * FROM student_course_history WHERE student_id=? ORDER BY id DESC LIMIT 1");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$history = $stmt2->get_result()->fetch_assoc() ?? [];

/* ================= COURSES ================= */
$courses = $conn->query("SELECT * FROM courses WHERE status=1");

/* ================= CURRENT VALUES ================= */
$current_course   = $history['course_id'] ?? '';
$current_discount = $history['discount'] ?? 0;
$current_days     = $history['days'] ?? '';
$current_time     = $history['time_slot'] ?? '';
$current_date     = $history['admission_date'] ?? date('Y-m-d');
$current_fee      = $history['course_fee'] ?? '';

/* ================= UPDATE ================= */
if(isset($_POST['update_student'])){

    $course_id      = $_POST['course_id'];
    $course_fee     = $_POST['course_fee']; // 🔥 important
    $discount       = $_POST['discount'] ?? 0;
    $days           = $_POST['days'];
    $time_slot      = $_POST['time_slot'];
    $admission_date = $_POST['admission_date'];
    $created_at     = date('Y-m-d H:i:s');

    /* CHECK EXIST */
    $check = $conn->prepare("
        SELECT id FROM student_course_history 
        WHERE student_id=? AND admission_date=?
    ");
    $check->bind_param("is", $id, $admission_date);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();

    if($exists){

        /* UPDATE */
        $h = $conn->prepare("
            UPDATE student_course_history
            SET course_id=?, course_fee=?, discount=?, time_slot=?, days=?, created_by=?, created_at=?
            WHERE id=?
        ");

        $h->bind_param("idissisi",
            $course_id,
            $course_fee,
            $discount,
            $time_slot,
            $days,
            $admin_id,
            $created_at,
            $exists['id']
        );

    } else {

        /* INSERT NEW */
        $h = $conn->prepare("
            INSERT INTO student_course_history
            (student_id, course_id, course_fee, admission_date, discount, time_slot, days, created_by, created_at)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");

        $h->bind_param("iidssssis",
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
    }

    $h->execute();

    logActivity($admin_id, $_SESSION['role'], 
        "Updated student | ID: $id | Course: $course_id | Fee: $course_fee"
    );

    header("Location: update_student.php?id=$id&updated=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Update Student Course</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<div class="d-flex">
<?php include("../includes/sidebar.php"); ?>

<div class="content flex-grow-1 p-4">

<h3>📚 Update Course Info</h3>

<?php if(isset($_GET['updated'])): ?>
<div class="alert alert-success">✅ Updated successfully</div>
<?php endif; ?>

<form method="POST" class="row g-3">

<!-- STUDENT INFO -->
<div class="col-md-3">
<label>GR No</label>
<input type="text" class="form-control" value="<?= $student['gr_no'] ?>" readonly>
</div>

<div class="col-md-6">
<label>Name</label>
<input type="text" class="form-control" value="<?= $student['name'] ?>" readonly>
</div>

<div class="col-md-6">
<label>Father Name</label>
<input type="text" class="form-control" value="<?= $student['father_name'] ?>" readonly>
</div>

<div class="col-md-6">
<label>Email</label>
<input type="text" class="form-control" value="<?= $student['email'] ?>" readonly>
</div>

<!-- COURSE -->
<div class="col-md-4">
<label>Course</label>
<select name="course_id" id="courseSelect" class="form-select" required>
<option value="">Select</option>
<?php while($c=$courses->fetch_assoc()): ?>
<option 
value="<?= $c['id'] ?>" 
data-fee="<?= $c['fees'] ?>"
<?= ($current_course==$c['id'])?'selected':'' ?>>
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<!-- FEE -->
<div class="col-md-4">
<label>Course Fee</label>
<input type="text" id="feeBox" class="form-control" readonly>
</div>

<!-- HIDDEN FEE -->
<input type="hidden" name="course_fee" id="courseFeeInput">

<!-- DISCOUNT -->
<div class="col-md-3">
<label>Discount</label>
<input type="number" name="discount" class="form-control" value="<?= $current_discount ?>">
</div>

<!-- DAYS -->
<div class="col-md-3">
<label>Days</label>
<select name="days" class="form-select">
<option value="MonWedFri" <?= ($current_days=='MonWedFri')?'selected':'' ?>>Mon Wed Fri</option>
<option value="TueThuSat" <?= ($current_days=='TueThuSat')?'selected':'' ?>>Tue Thu Sat</option>
</select>
</div>

<!-- TIME -->
<div class="col-md-3">
<label>Time Slot</label>
<select name="time_slot" class="form-select">
<option value="3-4:30" <?= ($current_time=='3-4:30')?'selected':'' ?>>3-4:30</option>
<option value="4:30-6" <?= ($current_time=='4:30-6')?'selected':'' ?>>4:30-6</option>
<option value="6-7:30" <?= ($current_time=='6-7:30')?'selected':'' ?>>6-7:30</option>
<option value="7:30-9" <?= ($current_time=='7:30-9')?'selected':'' ?>>7:30-9</option>
</select>
</div>

<!-- DATE -->
<div class="col-md-3">
<label>Admission Date</label>
<input type="date" name="admission_date" class="form-control" value="<?= $current_date ?>">
</div>

<button type="submit" name="update_student" class="btn btn-primary mt-3">💾 Update</button>

</form>

</div>
</div>

<script>
const courseSelect = document.getElementById('courseSelect');
const feeBox = document.getElementById('feeBox');
const feeInput = document.getElementById('courseFeeInput');

function updateFee(){
    const selected = courseSelect.options[courseSelect.selectedIndex];
    const fee = selected.getAttribute('data-fee') || '';

    feeBox.value = fee;
    feeInput.value = fee;
}

courseSelect.addEventListener('change', updateFee);
window.addEventListener('load', updateFee);
</script>

<?php include("../includes/footer.php"); ?>

</body>
</html>