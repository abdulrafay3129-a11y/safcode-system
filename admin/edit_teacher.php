<?php
include("../config/init.php");
checkRole(['admin']);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_teachers.php");
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM teachers WHERE teacher_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: view_teachers.php");
    exit;
}

$teacher = $result->fetch_assoc();
$message = "";

/* ================= UPDATE ================= */
if (isset($_POST['update_teacher'])) {

    $full_name = $_POST['full_name'];
    $email = strtolower($_POST['email']);
    $contact = $_POST['contact_number'];
    $salary_type = $_POST['salary_type'];
    $salary_amount = $_POST['salary_amount'];
    $cnic = $_POST['cnic'];
    $address = $_POST['address'];
    $joining_date = $_POST['joining_date'];
    $status = $_POST['status'];
    $password = $_POST['password'];

    if (!empty($password)) {

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            UPDATE teachers SET 
            full_name=?, email=?, contact_number=?, salary_type=?, salary_amount=?,
            cnic=?, address=?, joining_date=?, status=?, password=?
            WHERE teacher_id=?
        ");

        $stmt->bind_param(
            "ssssdsssssi",
            $full_name,
            $email,
            $contact,
            $salary_type,
            $salary_amount,
            $cnic,
            $address,
            $joining_date,
            $status,
            $hashed,
            $id
        );

    } else {

        $stmt = $conn->prepare("
            UPDATE teachers SET 
            full_name=?, email=?, contact_number=?, salary_type=?, salary_amount=?,
            cnic=?, address=?, joining_date=?, status=?
            WHERE teacher_id=?
        ");

        $stmt->bind_param(
            "ssssdssssi",
            $full_name,
            $email,
            $contact,
            $salary_type,
            $salary_amount,
            $cnic,
            $address,
            $joining_date,
            $status,
            $id
        );
    }

    if ($stmt->execute()) {

        // 🔥 USERS TABLE SYNC
        $u = $conn->prepare("UPDATE users SET name=?, email=? WHERE email=?");
        $u->bind_param("sss", $full_name, $email, $teacher['email']);
        $u->execute();

        logActivity($_SESSION['user_id'], $_SESSION['role'], "Updated teacher ID $id");

        $message = "✅ Teacher Updated Successfully!";
    }
}

/* ================= DELETE ================= */
if (isset($_POST['delete_teacher'])) {

    $stmt = $conn->prepare("DELETE FROM teachers WHERE teacher_id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        // users table delete
        $conn->query("DELETE FROM users WHERE email='".$teacher['email']."'");

        logActivity($_SESSION['user_id'], $_SESSION['role'], "Deleted teacher ID $id");

        header("Location: view_teachers.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Teacher</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

<div class="d-flex">

<?php include("../includes/sidebar.php"); ?>

<div class="content flex-grow-1 p-4">

<h3>Edit Teacher</h3>

<?php if($message): ?>
<div class="alert alert-info"><?= $message ?></div>
<?php endif; ?>

<form method="POST" class="row g-3" autocomplete="off">

<div class="col-md-4">
<label>Full Name</label>
<input type="text" name="full_name" class="form-control"
value="<?= htmlspecialchars($teacher['full_name']) ?>">
</div>

<div class="col-md-4">
<label>Email</label>
<input type="email" name="email" class="form-control"
value="<?= htmlspecialchars($teacher['email']) ?>">
</div>

<div class="col-md-4">
<label>Contact</label>
<input type="text" name="contact_number" class="form-control"
value="<?= htmlspecialchars($teacher['contact_number']) ?>">
</div>

<div class="col-md-4">
<label>Salary Type</label>
<select name="salary_type" class="form-select">
<option value="fixed" <?= $teacher['salary_type']=='fixed'?'selected':'' ?>>Fixed</option>
<option value="hourly" <?= $teacher['salary_type']=='hourly'?'selected':'' ?>>Hourly</option>
</select>
</div>

<div class="col-md-4">
<label>Salary Amount</label>
<input type="number" name="salary_amount" class="form-control"
value="<?= $teacher['salary_amount'] ?>">
</div>

<div class="col-md-4">
<label>CNIC</label>
<input type="text" name="cnic" class="form-control"
value="<?= htmlspecialchars($teacher['cnic']) ?>">
</div>

<div class="col-md-4">
<label>Joining Date</label>
<input type="date" name="joining_date" class="form-control"
value="<?= $teacher['joining_date'] ?>">
</div>

<div class="col-md-4">
<label>Status</label>
<select name="status" class="form-select">
<option value="active" <?= $teacher['status']=='active'?'selected':'' ?>>Active</option>
<option value="inactive" <?= $teacher['status']=='inactive'?'selected':'' ?>>Inactive</option>
</select>
</div>

<div class="col-md-12">
<label>Address</label>
<textarea name="address" class="form-control"><?= htmlspecialchars($teacher['address']) ?></textarea>
</div>

<div class="col-md-4">
<label>Change Password</label>
<input type="text" name="password" class="form-control" placeholder="Leave blank">
</div>

<div class="col-12">
<button name="update_teacher" class="btn btn-primary">Update Teacher</button>

<button type="submit"
name="delete_teacher"
class="btn btn-danger"
onclick="return confirm('Are you sure you want to delete this teacher?')">
Delete Teacher
</button>
</div>

</form>

</div>
</div>

<?php include("../includes/footer.php"); ?>

</body>
</html>