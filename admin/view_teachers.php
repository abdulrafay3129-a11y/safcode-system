<?php
include("../config/init.php");
checkRole(['admin']);

/* ================= DELETE ================= */
if (isset($_POST['delete_teacher'])) {

    $id = intval($_POST['teacher_id']);

    $del = $conn->prepare("DELETE FROM teachers WHERE teacher_id=?");
    $del->bind_param("i", $id);

    if ($del->execute()) {
        logActivity($_SESSION['user_id'], $_SESSION['role'], "Deleted teacher ID $id");
    }
}

/* ================= SEARCH ================= */
$search = $_GET['search'] ?? '';

if ($search != "") {

    $stmt = $conn->prepare("
        SELECT * FROM teachers 
        WHERE (full_name LIKE ? OR email LIKE ? OR contact_number LIKE ? OR cnic LIKE ?)
        ORDER BY teacher_id DESC
    ");

    $like = "%$search%";
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();

} else {
    // 🔥 FIX: NO status filter
    $result = $conn->query("SELECT * FROM teachers ORDER BY teacher_id DESC");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>View Teachers</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.main-content {
    padding: 20px;
    width: 100%;
}

.table-box {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.05);
}
</style>

</head>

<body>

<div class="d-flex">

<?php include("../includes/sidebar.php"); ?>

<div class="main-content flex-grow-1">

<div class="table-box">

<h3 class="mb-3">All Teachers</h3>

<!-- SEARCH -->
<form method="GET" class="row g-2 mb-3" autocomplete="off">
<div class="col-md-10">
<input type="text" name="search" class="form-control"
placeholder="Search teacher..."
value="<?= htmlspecialchars($search) ?>">
</div>
<div class="col-md-2">
<button class="btn btn-primary w-100">Search</button>
</div>
</form>

<table class="table table-bordered table-hover align-middle">
<thead class="table-dark">
<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Phone</th>
<th>Salary</th>
<th>CNIC</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>

<tbody>

<?php while($row = $result->fetch_assoc()): ?>
<tr>

<td><?= $row['teacher_id'] ?></td>

<td><?= htmlspecialchars($row['full_name']) ?></td>

<td><?= htmlspecialchars($row['email']) ?></td>

<td><?= htmlspecialchars($row['contact_number']) ?></td>

<td><?= $row['salary_amount'] ?></td>

<td><?= $row['cnic'] ?></td>

<td>
<?php if($row['status']=='active'): ?>
<span class="badge bg-success">Active</span>
<?php else: ?>
<span class="badge bg-danger">Inactive</span>
<?php endif; ?>
</td>

<td>

<a href="edit_teacher.php?id=<?= $row['teacher_id'] ?>"
class="btn btn-warning btn-sm">
Edit
</a>

<form method="POST" style="display:inline;">
<input type="hidden" name="teacher_id" value="<?= $row['teacher_id'] ?>">

<button type="submit"
name="delete_teacher"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this teacher?')">
Delete
</button>
</form>

</td>

</tr>
<?php endwhile; ?>

</tbody>
</table>

</div>
</div>

</div>

<?php include("../includes/footer.php"); ?>

</body>
</html>