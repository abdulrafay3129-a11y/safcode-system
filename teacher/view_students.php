<?php
include("../config/init.php");
checkRole(['teacher']);

/* ================= DELETE LOGIC ================= */

/* DELETE COURSE OR STUDENT */
if(isset($_GET['delete_course_id']) && isset($_GET['student_id'])){

    $course_history_id = intval($_GET['delete_course_id']);
    $student_id = intval($_GET['student_id']);

    // count courses of student
    $countQ = $conn->prepare("SELECT COUNT(*) as total FROM student_course_history WHERE student_id=?");
    $countQ->bind_param("i",$student_id);
    $countQ->execute();
    $count = $countQ->get_result()->fetch_assoc()['total'];

    if($count > 1){
        // delete only that course
        $del = $conn->prepare("DELETE FROM student_course_history WHERE id=?");
        $del->bind_param("i",$course_history_id);
        $del->execute();

        logActivity($_SESSION['user_id'], $_SESSION['role'], "Deleted Course ID: $course_history_id of Student: $student_id");

    } else {
        // only 1 course → delete student
        $delStudent = $conn->prepare("UPDATE students SET status=0 WHERE id=?");
        $delStudent->bind_param("i",$student_id);
        $delStudent->execute();

        logActivity($_SESSION['user_id'], $_SESSION['role'], "Deleted Student ID: $student_id");
    }

    header("Location: view_students.php");
    exit;
}


/* ================= FILTERS ================= */

$name  = $_GET['name'] ?? '';
$course_id = $_GET['course_id'] ?? '';
$days  = $_GET['days'] ?? '';
$time  = $_GET['time_slot'] ?? '';
$start = $_GET['start_date'] ?? '';
$end   = $_GET['end_date'] ?? '';

$courses = $conn->query("SELECT id, name FROM courses WHERE status=1");


/* ================= MAIN QUERY ================= */

$sql = "
SELECT 
    s.id as student_id,
    s.name,
    s.email,
    s.phone,
    c.name AS course_name,
    h.id as history_id,
    h.days,
    h.time_slot,
    h.admission_date
FROM students s
LEFT JOIN student_course_history h ON h.student_id = s.id
LEFT JOIN courses c ON c.id = h.course_id
WHERE s.status = 1
";

$params = [];
$types = "";

/* FILTERS */
if($name){
    $sql .= " AND s.name LIKE ?";
    $params[] = "%$name%";
    $types .= "s";
}

if($course_id){
    $sql .= " AND h.course_id = ?";
    $params[] = $course_id;
    $types .= "i";
}

if($days){
    $sql .= " AND h.days = ?";
    $params[] = $days;
    $types .= "s";
}

if($time){
    $sql .= " AND h.time_slot = ?";
    $params[] = $time;
    $types .= "s";
}

if($start && $end){
    $sql .= " AND DATE(h.admission_date) BETWEEN ? AND ?";
    $params[] = $start;
    $params[] = $end;
    $types .= "ss";
}

$sql .= " ORDER BY s.id DESC, h.id DESC";

$stmt = $conn->prepare($sql);
if($params){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>View Students</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background-color: #f4f6f9;
}

.card-box {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.table th {
    background: #343a40;
    color: #fff;
}

.action-btns {
    display: flex;
    gap: 5px;
}

.btn-sm {
    border-radius: 6px;
}
</style>
</head>

<body>

<div class="d-flex">
<?php include("../includes/sidebar.php"); ?>

<div class="content flex-grow-1 p-4">

<div class="card-box">

<h4 class="mb-3">📚 Students List</h4>

<!-- FILTER -->
<form method="GET" class="row g-2 mb-3">

<div class="col-md-2">
<input type="text" name="name" class="form-control" placeholder="Search Name" value="<?= htmlspecialchars($name) ?>">
</div>

<div class="col-md-2">
<select name="course_id" class="form-select">
<option value="">Course</option>
<?php while($c=$courses->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>" <?= $course_id==$c['id']?'selected':'' ?>>
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-2">
<select name="days" class="form-select">
<option value="">Days</option>
<option value="MonWedFri" <?= $days=='MonWedFri'?'selected':'' ?>>MonWedFri</option>
<option value="TueThuSat" <?= $days=='TueThuSat'?'selected':'' ?>>TueThuSat</option>
</select>
</div>

<div class="col-md-2">
<select name="time_slot" class="form-select">
<option value="">Time</option>
<option value="3-4:30" <?= $time=='3-4:30'?'selected':'' ?>>3-4:30</option>
<option value="4:30-6" <?= $time=='4:30-6'?'selected':'' ?>>4:30-6</option>
<option value="6-7:30" <?= $time=='6-7:30'?'selected':'' ?>>6-7:30</option>
</select>
</div>

<div class="col-md-2">
<input type="date" name="start_date" class="form-control" value="<?= $start ?>">
</div>

<div class="col-md-2">
<input type="date" name="end_date" class="form-control" value="<?= $end ?>">
</div>

<div class="col-md-12 mt-2">
<button class="btn btn-dark btn-sm">Filter</button>
<a href="view_students.php" class="btn btn-secondary btn-sm">Reset</a>
</div>

</form>

<!-- TABLE -->
<div class="table-responsive">
<table class="table table-bordered table-hover align-middle">

<thead>
<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Phone</th>
<th>Course</th>
<th>Days</th>
<th>Time</th>
<th>Admission</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php while($row = $result->fetch_assoc()): ?>
<tr>

<td><?= $row['student_id'] ?></td>
<td><?= htmlspecialchars($row['name']) ?></td>
<td><?= htmlspecialchars($row['email']) ?></td>
<td><?= htmlspecialchars($row['phone']) ?></td>
<td><?= $row['course_name'] ?></td>
<td><?= $row['days'] ?></td>
<td><?= $row['time_slot'] ?></td>
<td><?= date("d-M-Y", strtotime($row['admission_date'])) ?></td>

<td>
<div class="action-btns">

<a href="add_another_course.php?id=<?= $row['student_id'] ?>" class="btn btn-success btn-sm">+Course</a>

<a href="edit_student.php?id=<?= $row['student_id'] ?>" class="btn btn-warning btn-sm">Edit</a>

<a href="?delete_course_id=<?= $row['history_id'] ?>&student_id=<?= $row['student_id'] ?>" 
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this course? If it is last course, student will be deleted.')">
Delete
</a>

<a href="admit_card.php?id=<?= $row['student_id'] ?>" class="btn btn-info btn-sm text-white">Admit</a>

</div>
</td>

</tr>
<?php endwhile; ?>

</tbody>

</table>
</div>

</div>

</div>
</div>

<?php include("../includes/footer.php"); ?>

</body>
</html>