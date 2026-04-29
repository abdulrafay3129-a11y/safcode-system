<?php
include("../config/init.php");
checkRole(['admin']);

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

logActivity($user_id,$role,"Viewed Attendance");

$selected_date = $_GET['date'] ?? date('Y-m-d');
$course_id = $_GET['course_id'] ?? '';
$days = $_GET['days'] ?? '';
$time_slot = $_GET['time_slot'] ?? '';

/* DELETE AJAX */
if(isset($_POST['delete_id'])){
    $id = (int)$_POST['delete_id'];

    if($conn->query("DELETE FROM attendance WHERE id=$id")){
        logActivity($user_id,$role,"Deleted attendance ID $id");
        echo "success";
    } else {
        echo "error";
    }
    exit;
}

/* ✅ FIXED QUERY (ONLY MARKED RECORDS) */
$sql = "
SELECT 
    s.id as student_id,
    s.name,
    s.gr_no,
    s.father_name,
    sch.course_id,
    sch.days,
    sch.time_slot,
    a.id as attendance_id,
    a.status

FROM students s

JOIN student_course_history sch 
    ON sch.student_id = s.id

JOIN attendance a 
    ON a.student_id = s.id 
    AND a.course_id = sch.course_id
    AND a.days = sch.days
    AND a.time_slot = sch.time_slot
    AND a.attendance_date = ?

WHERE s.status = 1
";

$params = [$selected_date];
$types="s";

if($course_id){
    $sql.=" AND sch.course_id=?";
    $types.="i"; $params[]=$course_id;
}
if($days){
    $sql.=" AND sch.days=?";
    $types.="s"; $params[]=$days;
}
if($time_slot){
    $sql.=" AND sch.time_slot=?";
    $types.="s"; $params[]=$time_slot;
}

$sql.=" GROUP BY s.id, sch.course_id, sch.days, sch.time_slot";
$sql.=" ORDER BY s.name";

$stmt=$conn->prepare($sql);
$stmt->bind_param($types,...$params);
$stmt->execute();
$data=$stmt->get_result();

$courses=$conn->query("SELECT id,name FROM courses WHERE status=1");
?>

<!DOCTYPE html>
<html>
<head>
<title>Attendance View</title>

<link rel="stylesheet" href="../assets/css/admin.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.wrapper{
    display:flex;
}
.content{
    flex:1;
    padding:20px;
}
.table td, .table th{
    vertical-align: middle;
}
</style>
</head>

<body>

<div class="wrapper">
<?php include("../includes/sidebar.php"); ?>

<div class="content">

<h3>📊 Attendance View</h3>

<div id="msgBox"></div>

<!-- FILTER -->
<form method="GET" class="row g-2 mb-3">

<div class="col-md-2">
<input type="date" name="date" value="<?= $selected_date ?>" class="form-control">
</div>

<div class="col-md-2">
<select name="course_id" class="form-select">
<option value="">Course</option>
<?php while($c=$courses->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>" <?= ($course_id==$c['id'])?'selected':'' ?>>
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-2">
<select name="days" class="form-select">
<option value="">Days</option>
<option value="MonWedFri" <?= ($days=='MonWedFri')?'selected':'' ?>>Mon Wed Fri</option>
<option value="TueThuSat" <?= ($days=='TueThuSat')?'selected':'' ?>>Tue Thu Sat</option>
</select>
</div>

<div class="col-md-2">
<select name="time_slot" class="form-select">
<option value="">Time</option>
<option value="3-4:30" <?= ($time_slot=='3-4:30')?'selected':'' ?>>3-4:30</option>
<option value="4:30-6" <?= ($time_slot=='4:30-6')?'selected':'' ?>>4:30-6</option>
<option value="6-7:30" <?= ($time_slot=='6-7:30')?'selected':'' ?>>6-7:30</option>
<option value="7:30-9" <?= ($time_slot=='7:30-9')?'selected':'' ?>>7:30-9</option>
</select>
</div>

<div class="col-md-2">
<button class="btn btn-primary w-100">Filter</button>
</div>

<div class="col-md-2">
<a href="attendance_summary.php" class="btn btn-success w-100">Summary</a>
</div>

</form>

<!-- TABLE -->
<table class="table table-bordered">
<thead class="table-dark">
<tr>
<th>GR</th>
<th>Name</th>
<th>Father</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>

<tbody>
<?php if($data->num_rows): ?>
<?php while($r=$data->fetch_assoc()): ?>
<tr id="row<?= $r['attendance_id'] ?>">

<td><?= $r['gr_no'] ?></td>
<td><?= $r['name'] ?></td>
<td><?= $r['father_name'] ?></td>

<td>
<span class="badge bg-<?= $r['status']=='Present'?'success':'danger' ?>">
<?= $r['status'] ?>
</span>
</td>

<td>
<a href="attendance_detail.php?student_id=<?= $r['student_id'] ?>" class="btn btn-info btn-sm">Edit</a>

<button class="btn btn-danger btn-sm deleteBtn" data-id="<?= $r['attendance_id'] ?>">
Delete
</button>

</td>

</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="5" class="text-center">No Record</td></tr>
<?php endif; ?>
</tbody>
</table>

</div>
</div>

<script>

/* DELETE */
document.querySelectorAll('.deleteBtn').forEach(btn=>{
btn.addEventListener('click',function(){

if(!confirm('Are you sure?')) return;

let id=this.getAttribute('data-id');
let row=document.getElementById("row"+id);

fetch('attendance_view.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:'delete_id='+id
})
.then(res=>res.text())
.then(res=>{
if(res.trim()==='success'){
row.remove();
document.getElementById('msgBox').innerHTML =
'<div class="alert alert-success">Deleted Successfully</div>';
}else{
document.getElementById('msgBox').innerHTML =
'<div class="alert alert-danger">Delete Failed</div>';
}
});
});
});

</script>

<?php include("../includes/footer.php"); ?>

</body>
</html>