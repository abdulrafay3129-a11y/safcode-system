<?php
include("../config/init.php");
checkRole(['admin']);

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM courses WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if(!$course){
    die("❌ Course not found");
}

if(isset($_POST['update_course'])){

    $name = $_POST['name'];
    $duration = $_POST['duration'];
    $fees = (float)$_POST['fees'];
    $description = $_POST['description'];
    $status = (int)$_POST['status'];

    $stmt = $conn->prepare("
        UPDATE courses 
        SET name=?, description=?, duration=?, fees=?, status=? 
        WHERE id=?
    ");

    $stmt->bind_param(
        "sssdis",
        $name,
        $description,
        $duration,
        $fees,
        $status,
        $id
    );

    if($stmt->execute()){

        logActivity(
            $_SESSION['user_id'],
            $_SESSION['role'],
            "Course updated | ID: $id | Name: $name"
        );

        $success = "Course updated successfully!";
    } else {
        $error = "❌ Update failed!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Course</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="d-flex">

<?php include("../includes/sidebar.php"); ?>

<div class="content flex-grow-1 p-4">

<h3>Edit Course</h3>

<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>

<form method="POST">

<input type="text" name="name" value="<?= $course['name'] ?>" class="form-control mb-2">

<select name="duration" class="form-select mb-2">
<option <?= $course['duration']=="1 Month"?'selected':'' ?>>1 Month</option>
<option <?= $course['duration']=="2 Months"?'selected':'' ?>>2 Months</option>
<option <?= $course['duration']=="4 Months"?'selected':'' ?>>4 Months</option>
<option <?= $course['duration']=="6 Months"?'selected':'' ?>>6 Months</option>
</select>

<input type="number" name="fees" value="<?= $course['fees'] ?>" class="form-control mb-2">

<textarea name="description" class="form-control mb-2"><?= $course['description'] ?></textarea>

<select name="status" class="form-select mb-2">
<option value="1" <?= $course['status']==1?'selected':'' ?>>Active</option>
<option value="0" <?= $course['status']==0?'selected':'' ?>>Inactive</option>
</select>

<button name="update_course" class="btn btn-primary">Update</button>

</form>

</div>
</div>

<?php include("../includes/footer.php"); ?>
</body>
</html>