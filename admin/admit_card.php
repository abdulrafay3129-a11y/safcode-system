<?php
include("../config/init.php");
checkRole(['admin']);

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// ID check
if(!isset($_GET['id'])){
    header("Location: view_students.php");
    exit;
}

$id = intval($_GET['id']);

// Fetch student basic info
$stmt = $conn->prepare("SELECT * FROM students WHERE id=? AND status=1");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if(!$student){
    die("Student not found");
}

// Fetch latest course history
$stmt2 = $conn->prepare("
    SELECT sch.*, c.name AS course_name
    FROM student_course_history sch
    JOIN courses c ON sch.course_id = c.id
    WHERE sch.student_id = ?
    ORDER BY sch.id DESC
    LIMIT 1
");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$courseData = $stmt2->get_result()->fetch_assoc();

// LOGGER
if(function_exists('logActivity')){
    logActivity(
        $_SESSION['user_id'],
        $_SESSION['role'],
        "Viewed admit card: ".$student['name']." (ID: ".$student['id'].")"
    );
}

// PHOTO
$photoPath = "https://via.placeholder.com/75";

if(!empty($student['photo']) && file_exists("../uploads/students/".$student['photo'])){
    $photoPath = "../uploads/students/".$student['photo'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admit Card</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.card-box{
border:2px solid black;
margin:auto;
margin-top:20px;
width:440px;
background:white;
border-radius:6px;
overflow:hidden;
}

.header{
text-align:center;
background:#0b3d91;
color:white;
padding:20px 10px;
height:90px;
display:flex;
align-items:center;
justify-content:center;
}

.logo{
width:240px;
height:auto;
}

.details{
padding:15px;
font-size:15px;
}

.photo-box{
text-align:center;
margin-bottom:8px;
}

.photo-preview{
width:75px;
height:75px;
border:2px solid black;
object-fit:cover;
margin-bottom:5px;
}

table{
width:100%;
}

td{
padding:6px 4px;
}

.label{
width:160px;
font-weight:bold;
}

.value{
border-bottom:2px solid black;
font-weight:bold;
padding-left:8px;
height:24px;
text-transform:capitalize;
}

.footer{
margin-top:15px;
background:#0b3d91;
color:white;
padding:10px;
text-align:center;
font-size:12px;
font-weight:bold;
border-top:3px solid #082c6c;
}

/* PRINT */
@media print{
.sidebar{display:none;}
.card-box{margin-top:5px;width:420px;}
.header{
background:#0b3d91 !important;
color:white !important;
-webkit-print-color-adjust: exact;
print-color-adjust: exact;
}
.footer{
background:#0b3d91 !important;
color:white !important;
-webkit-print-color-adjust: exact;
print-color-adjust: exact;
}
}
</style>

<script>
function previewImage(event)
{
    let reader=new FileReader();
    reader.onload=function(){
        document.getElementById("photo").src=reader.result;
    }
    reader.readAsDataURL(event.target.files[0]);
}
</script>

</head>

<body>

<div class="d-flex">

<?php include("../includes/sidebar.php"); ?>

<div class="flex-grow-1">

<div class="container">

<div class="card-box">

<!-- HEADER -->
<div class="header">
<img src="../admin/safcode_logo.png" class="logo">
</div>

<div class="details">

<!-- PHOTO -->
<div class="photo-box">
<img src="<?= $photoPath ?>" id="photo" class="photo-preview"><br>

<input type="file" accept="image/png, image/jpeg"
class="form-control form-control-sm"
onchange="previewImage(event)">
</div>

<table>
<tr>
<td class="label">Name :</td>
<td class="value"><?= ucwords(strtolower($student['name'])) ?></td>
</tr>

<tr>
<td class="label">Father Name :</td>
<td class="value"><?= ucwords(strtolower($student['father_name'])) ?></td>
</tr>

<tr>
<td class="label">Course :</td>
<td class="value"><?= $courseData['course_name'] ?? 'N/A' ?></td>
</tr>

<tr>
<td class="label">Date of Admission :</td>
<td class="value">
<?= isset($courseData['admission_date']) ? date("d-M-Y",strtotime($courseData['admission_date'])) : 'N/A' ?>
</td>
</tr>

<tr>
<td class="label">Signature :</td>
<td class="value"></td>
</tr>
</table>

</div>

<!-- FOOTER -->
<div class="footer">
<div>📍 R-72, Sector 11-B, North Karachi</div>
<span>📞 Cell: 0313-8389893</span>
</div>

</div>

</div>

</div>

</div>

<?php include("../includes/footer.php"); ?>

</body>
</html>