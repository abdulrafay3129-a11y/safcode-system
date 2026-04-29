<?php
include("../config/init.php");
checkRole(['teacher']);

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

/* ✅ VIEW LOG */
logActivity($user_id,$role,"Opened Attendance Page");

$courses = $conn->query("SELECT * FROM courses WHERE status=1");

$students = [];

$date = $_POST['attendance_date'] ?? date('Y-m-d');
$course_id = $_POST['course_id'] ?? '';
$days = $_POST['days'] ?? '';
$time_slot = $_POST['time_slot'] ?? '';

/* LOAD STUDENTS */
if(isset($_POST['filter']) && $course_id != ''){

logActivity($user_id,$role,"Filtered Attendance Students");

$sql = "
SELECT 
    s.id, 
    s.name, 
    s.gr_no, 
    s.father_name,
    sch.course_id,
    sch.days,
    sch.time_slot
FROM students s
JOIN student_course_history sch ON sch.student_id = s.id
WHERE s.status=1
";

$sql .= " AND sch.course_id=".(int)$course_id;

if($days != ''){
$sql .= " AND sch.days='".$conn->real_escape_string($days)."'";
}
if($time_slot != ''){
$sql .= " AND sch.time_slot='".$conn->real_escape_string($time_slot)."'";
}

$res = $conn->query($sql);

if($res){
while($r=$res->fetch_assoc()){
$students[] = $r;
}
}
}

/* SAVE ATTENDANCE */
if(isset($_POST['save']) && isset($_POST['status'])){

foreach($_POST['status'] as $sid=>$st){

$q=$conn->prepare("SELECT id FROM attendance 
WHERE student_id=? AND attendance_date=? AND course_id=? AND days=? AND time_slot=?");

$q->bind_param("isiss",$sid,$date,$course_id,$days,$time_slot);
$q->execute();

$exists = $q->get_result()->num_rows;

if($exists){

$u=$conn->prepare("UPDATE attendance SET status=? 
WHERE student_id=? AND attendance_date=? AND course_id=? AND days=? AND time_slot=?");

$u->bind_param("sisiss",$st,$sid,$date,$course_id,$days,$time_slot);

}else{

$u=$conn->prepare("INSERT INTO attendance
(student_id,course_id,days,time_slot,status,attendance_date,marked_by,role)
VALUES(?,?,?,?,?,?,?,?)");

$u->bind_param("iissssss",$sid,$course_id,$days,$time_slot,$st,$date,$user_id,$role);
}

$u->execute();
}

/* ✅ SAVE LOG */
logActivity($user_id,$role,"Saved Attendance for date: $date");

header("Location: attendance.php?success=1");
exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.wrapper{display:flex;}
.content{flex:1;padding:20px;}
</style>
</head>

<body>

<div class="wrapper">
<?php include("../includes/sidebar.php"); ?>

<div class="content">

<h3>Attendance System</h3>

<?php if(isset($_GET['success'])): ?>
<div class="alert alert-success">Attendance Saved Successfully</div>
<?php endif; ?>

<form method="POST" class="row g-2 mb-3" autocomplete="off">

<div class="col-md-3">
<select name="course_id" class="form-select" required>
<option value="">Select Course</option>
<?php while($c=$courses->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>" <?= ($course_id==$c['id'])?'selected':'' ?>>
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-2">
<select name="days" class="form-select" required>
<option value="">Days</option>
<option value="MonWedFri" <?= ($days=='MonWedFri')?'selected':'' ?>>Mon Wed Fri</option>
<option value="TueThuSat" <?= ($days=='TueThuSat')?'selected':'' ?>>Tue Thu Sat</option>
</select>
</div>

<div class="col-md-2">
<select name="time_slot" class="form-select" required>
<option value="">Time</option>
<option value="3-4:30">3-4:30</option>
<option value="4:30-6">4:30-6</option>
<option value="6-7:30">6-7:30</option>
<option value="7:30-9">7:30-9</option>
</select>
</div>

<div class="col-md-2">
<input type="date" name="attendance_date" value="<?= $date ?>" class="form-control">
</div>

<div class="col-md-2">
<button name="filter" class="btn btn-primary w-100">Load Students</button>
</div>

</form>

<?php if(isset($_POST['filter']) && empty($students)): ?>
<div class="alert alert-danger">Student Not Found</div>
<?php endif; ?>

<?php if(!empty($students)): ?>

<form method="POST" autocomplete="off">

<input type="hidden" name="course_id" value="<?= $course_id ?>">
<input type="hidden" name="days" value="<?= $days ?>">
<input type="hidden" name="time_slot" value="<?= $time_slot ?>">
<input type="hidden" name="attendance_date" value="<?= $date ?>">

<div class="mb-2">
<button type="button" class="btn btn-success btn-sm" onclick="selectAll('Present')">Select All Present</button>
<button type="button" class="btn btn-danger btn-sm" onclick="selectAll('Absent')">Select All Absent</button>
</div>

<table class="table table-bordered">
<tr>
<th>GR</th>
<th>Name</th>
<th>Father</th>
<th>P</th>
<th>A</th>
</tr>

<?php foreach($students as $s): ?>
<tr>
<td><?= $s['gr_no'] ?></td>
<td><?= $s['name'] ?></td>
<td><?= $s['father_name'] ?></td>

<td><input type="radio" name="status[<?= $s['id'] ?>]" value="Present" checked></td>
<td><input type="radio" name="status[<?= $s['id'] ?>]" value="Absent"></td>
</tr>
<?php endforeach; ?>

</table>

<button name="save" class="btn btn-success w-100">Save Attendance</button>
</form>

<?php endif; ?>

</div>
</div>

<script>
function selectAll(status){
document.querySelectorAll('input[type=radio]').forEach(r=>{
if(r.value === status){ r.checked = true; }
});
}
</script>

</body>
</html>