<?php
include("../config/init.php");
checkRole(['admin']);
$fees = [];
$attendance = [];
$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

if(isset($_POST['add_course'])){

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $duration = $_POST['duration'];
    $fees = (float)$_POST['fees'];

    if($name == "" || $fees <= 0){
        $error = "❌ Name aur valid fee required hai!";
    } else {

        // check duplicate
        $check = $conn->prepare("SELECT id FROM courses WHERE name=?");
        $check->bind_param("s", $name);
        $check->execute();
        $res = $check->get_result();

        if($res->num_rows > 0){
            $error = "❌ Course already exists!";
        } else {

            $stmt = $conn->prepare("
                INSERT INTO courses (name, description, duration, fees, status)
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->bind_param("sssd", $name, $description, $duration, $fees);

            if($stmt->execute()){

                logActivity(
                    $_SESSION['user_id'],
                    $_SESSION['role'],
                    "Course added | Name: $name | Fees: $fees"
                );

                $success = "✅ Course added successfully!";
            } else {
                $error = "❌ DB Error: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Course | Safcode IMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
<div class="d-flex">

<?php include("../includes/sidebar.php"); ?>

<div class="content flex-grow-1 p-4">

<h3>Add Course</h3>

<?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>

<form method="POST">

<div class="mb-3">
<label>Course Name</label>
<input type="text" name="name" class="form-control" required>
</div>

<div class="mb-3">
<label>Duration</label>
<select name="duration" class="form-select" required>
<option value="">Select</option>
<option>1 Month</option>
<option>2 Months</option>
<option>4 Months</option>
<option>6 Months</option>
</select>
</div>

<div class="mb-3">
<label>Fees</label>
<input type="number" name="fees" class="form-control" required>
</div>

<div class="mb-3">
<label>Description</label>
<textarea name="description" class="form-control"></textarea>
</div>

<button type="submit" name="add_course" class="btn btn-primary">Add Course</button>

</form>

</div>
</div>

<?php include("../includes/footer.php"); ?>
</body>
</html>