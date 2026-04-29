<?php
include("../config/init.php");
checkRole(['admin']);

$id = intval($_GET['id']);

/* STUDENT */
$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if(!$student){
    die("Student not found");
}

/* COURSE */
$stmt2 = $conn->prepare("
SELECT * FROM student_course_history 
WHERE student_id=? ORDER BY id DESC LIMIT 1
");
$stmt2->bind_param("i",$id);
$stmt2->execute();
$course = $stmt2->get_result()->fetch_assoc();

/* COURSES */
$courses = $conn->query("SELECT * FROM courses WHERE status=1");

/* UPDATE */
if(isset($_POST['edit_student'])){

    $name = $_POST['name'];
    $father_name = $_POST['father_name'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $status = $_POST['status'];

    $course_id = $_POST['course_id'];
    $days = $_POST['days'];
    $time_slot = $_POST['time_slot'];
    $course_fee = $_POST['course_fee'];
    $admission_date = $_POST['admission_date']; // ✅ FIX

    $photo = $student['photo'];

    if(!empty($_FILES['photo']['name'])){
        $file = time().'_'.$_FILES['photo']['name'];
        move_uploaded_file($_FILES['photo']['tmp_name'],"../uploads/students/".$file);
        $photo = $file;
    }

    /* UPDATE STUDENT */
    $stmt = $conn->prepare("
    UPDATE students 
    SET name=?, father_name=?, email=?, gender=?, status=?, photo=?, admission_date=? 
    WHERE id=?
    ");
    $stmt->bind_param("sssssssi",$name,$father_name,$email,$gender,$status,$photo,$admission_date,$id);
    $stmt->execute();

    /* UPDATE COURSE */
    if($course){
        $up = $conn->prepare("
        UPDATE student_course_history 
        SET course_id=?, course_fee=?, days=?, time_slot=?, admission_date=? 
        WHERE id=?
        ");
        $up->bind_param("idsssi",$course_id,$course_fee,$days,$time_slot,$admission_date,$course['id']);
        $up->execute();
    }

    header("Location: edit_student.php?id=$id&updated=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Student</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<div class="d-flex">
<?php include("../includes/sidebar.php"); ?>

<div class="content p-4 w-100">

<h4>Edit Student</h4>

<?php if(isset($_GET['updated'])): ?>
<div class="alert alert-success">Updated Successfully</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="row g-3">

<div class="col-md-3">
<label>GR</label>
<input class="form-control" value="<?= $student['gr_no'] ?>" readonly>
</div>

<!-- ✅ ADMISSION DATE ADDED -->
<div class="col-md-3">
<label>Admission Date</label>
<input type="date" name="admission_date" 
value="<?= $course['admission_date'] ?? $student['admission_date'] ?>" 
class="form-control">
</div>

<div class="col-md-6">
<label>Name</label>
<input name="name" value="<?= $student['name'] ?>" class="form-control">
</div>

<div class="col-md-6">
<label>Father</label>
<input name="father_name" value="<?= $student['father_name'] ?>" class="form-control">
</div>

<div class="col-md-6">
<label>Email</label>
<input name="email" value="<?= $student['email'] ?>" class="form-control">
</div>

<div class="col-md-3">
<label>Gender</label>
<select name="gender" class="form-select">
<option <?= $student['gender']=='Male'?'selected':'' ?>>Male</option>
<option <?= $student['gender']=='Female'?'selected':'' ?>>Female</option>
</select>
</div>

<div class="col-md-3">
<label>Status</label>
<select name="status" class="form-select">
<option value="active">Active</option>
<option value="inactive">Inactive</option>
<option value="dropout">Dropout</option>
<option value="completed">Completed</option>
</select>
</div>

<div class="col-md-4">
<label>Course</label>
<select name="course_id" id="courseSelect" class="form-select">
<?php while($c=$courses->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>" data-fee="<?= $c['fees'] ?>"
<?= ($course && $course['course_id']==$c['id'])?'selected':'' ?>>
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-4">
<label>Fee</label>
<input id="feeBox" class="form-control" readonly>
<input type="hidden" name="course_fee" id="feeInput">
</div>

<div class="col-md-3">
<label>Days</label>
<select name="days" class="form-select">
<option value="MonWedFri">MonWedFri</option>
<option value="TueThuSat">TueThuSat</option>
</select>
</div>

<div class="col-md-3">
<label>Time</label>
<select name="time_slot" class="form-select">
<option value="3-4:30">3-4:30</option>
<option value="4:30-6">4:30-6</option>
<option value="6-7:30">6-7:30</option>
</select>
</div>

<div class="col-md-4">
<label>Photo</label>
<input type="file" name="photo" class="form-control">
</div>

<div class="col-12">
<button name="edit_student" class="btn btn-primary">Update</button>
</div>

</form>

</div>
</div>

<script>
const select = document.getElementById("courseSelect");
const feeBox = document.getElementById("feeBox");
const feeInput = document.getElementById("feeInput");

function updateFee(){
    let fee = select.options[select.selectedIndex].getAttribute("data-fee");
    feeBox.value = fee;
    feeInput.value = fee;
}
select.addEventListener("change", updateFee);
window.onload = updateFee;
</script>

</body>
</html>