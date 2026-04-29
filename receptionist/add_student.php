<?php
include("../config/init.php");
checkRole(['receptionist']);

$message = "";

/* COURSES */
$courses = $conn->query("SELECT id, name, fees FROM courses WHERE status=1");

/* GR */
$last = $conn->query("SELECT gr_no FROM students ORDER BY id DESC LIMIT 1")->fetch_assoc();
$last_gr = $last['gr_no'] ?? null;

if($last_gr){
    $parts = explode('-', $last_gr);
    $next = str_pad($parts[1]+1,3,'0',STR_PAD_LEFT);
    $next_gr = $parts[0].'-'.$next;
}else{
    $next_gr = date('Y').'-001';
}

if(isset($_POST['add_student'])){

    $gr_no = $next_gr;
    $admission_date = $_POST['admission_date'];

    $name = $_POST['name'];
    $father_name = $_POST['father_name'];
    $gender = $_POST['gender'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $course_id = $_POST['course_id'];
    $discount = $_POST['discount'];
    $days = $_POST['days'];
    $time_slot = $_POST['time_slot'];

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $created_by = $_SESSION['user_id'];
    $role = $_SESSION['role']; // ✅ dynamic role

    /* PHOTO */
    $photo = null;
    if(!empty($_FILES['photo']['name'])){
        $dir = "../uploads/students/";
        if(!is_dir($dir)) mkdir($dir,0777,true);

        $file = time().'_'.$_FILES['photo']['name'];
        move_uploaded_file($_FILES['photo']['tmp_name'],$dir.$file);
        $photo = $file;
    }

    $stmt = $conn->prepare("
    INSERT INTO students (gr_no,admission_date,name,father_name,gender,email,phone,photo,created_by)
    VALUES (?,?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param("ssssssssi",$gr_no,$admission_date,$name,$father_name,$gender,$email,$phone,$photo,$created_by);

    if($stmt->execute()){

        $sid = $conn->insert_id;

        /* ✅ LOG FIXED */
        logActivity($created_by,$role,"Added new student: $name (GR: $gr_no)");

        /* COURSE FEE */
        $q = $conn->prepare("SELECT fees FROM courses WHERE id=?");
        $q->bind_param("i",$course_id);
        $q->execute();
        $fee = $q->get_result()->fetch_assoc()['fees'];

        $h = $conn->prepare("
        INSERT INTO student_course_history
        (student_id,course_id,course_fee,admission_date,discount,time_slot,days,created_by)
        VALUES (?,?,?,?,?,?,?,?)
        ");

        $h->bind_param("iidssssi",$sid,$course_id,$fee,$admission_date,$discount,$time_slot,$days,$created_by);
        $h->execute();

        $u = $conn->prepare("INSERT INTO users(name,email,password,role,status) VALUES(?,?,?,?,1)");
        $user_role="student";
        $u->bind_param("ssss",$name,$email,$password,$user_role);
        $u->execute();

        $message="Student Added Successfully";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Student</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="d-flex">
<?php include("../includes/sidebar.php"); ?>

<div class="content p-4 flex-grow-1">

<h3>Add Student</h3>

<?php if($message): ?>
<div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="row g-3" autocomplete="off">

<div class="col-md-3">
<label>GR No</label>
<input class="form-control" value="<?= $next_gr ?>" readonly>
</div>

<div class="col-md-3">
<label>Date</label>
<input type="date" name="admission_date" class="form-control" value="<?= date('Y-m-d') ?>">
</div>

<div class="col-md-3">
<label>Name</label>
<input name="name" class="form-control" required>
</div>

<div class="col-md-3">
<label>Father</label>
<input name="father_name" class="form-control">
</div>

<div class="col-md-3">
<label>Gender</label>
<select name="gender" class="form-select">
<option>Male</option>
<option>Female</option>
</select>
</div>

<div class="col-md-3">
<label>Email</label>
<input name="email" class="form-control">
</div>

<div class="col-md-3">
<label>Phone</label>
<input name="phone" class="form-control">
</div>

<div class="col-md-3">
<label>Course</label>
<select name="course_id" id="courseSelect" class="form-select">
<?php while($c=$courses->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>" data-fee="<?= $c['fees'] ?>">
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-3">
<label>Fee</label>
<input id="feeBox" class="form-control" readonly>
</div>

<div class="col-md-3">
<label>Discount</label>
<input name="discount" class="form-control" value="0">
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
</select>
</div>

<div class="col-md-3">
<label>Password</label>
<input name="password" class="form-control" value="student123">
</div>

<div class="col-md-3">
<label>Photo</label>
<input type="file" name="photo" class="form-control">
</div>

<div class="col-12">
<button class="btn btn-primary" name="add_student">Add</button>
</div>

</form>

</div>
</div>

<script>
const select = document.getElementById("courseSelect");
const feeBox = document.getElementById("feeBox");

function updateFee(){
    let fee = select.options[select.selectedIndex].getAttribute("data-fee");
    feeBox.value = fee;
}

select.addEventListener("change", updateFee);
window.onload = updateFee;
</script>

</body>
</html>