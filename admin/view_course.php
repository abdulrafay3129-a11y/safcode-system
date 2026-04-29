<?php
include("../config/init.php");
checkRole(['admin']);

if(isset($_GET['delete'])){

    $id = (int)$_GET['delete'];

    // get name before delete
    $get = $conn->prepare("SELECT name FROM courses WHERE id=?");
    $get->bind_param("i", $id);
    $get->execute();
    $course = $get->get_result()->fetch_assoc();

    $stmt = $conn->prepare("DELETE FROM courses WHERE id=?");
    $stmt->bind_param("i", $id);

    if($stmt->execute()){

        logActivity(
            $_SESSION['user_id'],
            $_SESSION['role'],
            "Course deleted | ID: $id | Name: ".$course['name']
        );

        $success = "Course deleted successfully!";
    }
}

$courses = $conn->query("SELECT * FROM courses ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
<title>View Courses</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="d-flex">

<?php include("../includes/sidebar.php"); ?>

<div class="content flex-grow-1 p-4">

<h3>Courses</h3>

<?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>

<table class="table table-bordered">

<tr>
<th>ID</th>
<th>Name</th>
<th>Duration</th>
<th>Fee</th>
<th>Description</th>
<th>Action</th>
</tr>

<?php while($row=$courses->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['name'] ?></td>
<td><?= $row['duration'] ?></td>
<td><?= $row['fees'] ?></td>
<td><?= $row['description'] ?></td>
<td>
<a href="edit_course.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
<a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</a>
</td>
</tr>
<?php endwhile; ?>

</table>

</div>
</div>

<?php include("../includes/footer.php"); ?>
</body>
</html>