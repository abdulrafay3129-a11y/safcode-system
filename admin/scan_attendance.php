<?php
include("../config/init.php");
checkRole(['admin']);

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Scanner Attendance</title>

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

<h3>📡 Scanner Attendance System</h3>

<!-- MESSAGE -->
<div id="msg"></div>

<!-- SCANNER -->
<input type="text" id="barcodeInput" class="form-control"
placeholder="Scan Barcode..." autofocus>

</div>
</div>

<script>
document.getElementById("barcodeInput").addEventListener("change", function(){

let code = this.value.trim();
if(!code) return;

// STEP 1: find student
fetch("find_student.php?code="+code)
.then(res=>res.json())
.then(data=>{

if(data.success){

let id = data.student.id;

// STEP 2: auto save attendance
fetch("save_scan_attendance.php",{
method:"POST",
headers:{"Content-Type":"application/x-www-form-urlencoded"},
body:"student_id="+id
})
.then(r=>r.text())
.then(res=>{

document.getElementById("msg").innerHTML =
'<div class="alert alert-success">Attendance Saved Successfully</div>';

});

}else{

document.getElementById("msg").innerHTML =
'<div class="alert alert-danger">Student Not Found</div>';

}

this.value="";
});

});
</script>

</body>
</html>