<?php
include("../config/init.php");
checkRole(['admin']);

$id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if(!$user) die("User not found");

$error = "";

if(isset($_POST['update'])){

    $name   = trim($_POST['name']);
    $email  = strtolower(trim($_POST['email']));
    $role   = $_POST['role'];
    $status = (int)$_POST['status'];
    $password = $_POST['password'];

    $check = $conn->prepare("SELECT id FROM users WHERE email=? AND id!=?");
    $check->bind_param("si",$email,$id);
    $check->execute();

    if($check->get_result()->num_rows > 0){
        $error = "❌ Email already exists!";
    } else {

        if(!empty($password)){
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                UPDATE users 
                SET name=?, email=?, role=?, status=?, password=? 
                WHERE id=?
            ");
            $stmt->bind_param("sssssi",$name,$email,$role,$status,$hashed,$id);

        } else {

            $stmt = $conn->prepare("
                UPDATE users 
                SET name=?, email=?, role=?, status=? 
                WHERE id=?
            ");
            $stmt->bind_param("sssii",$name,$email,$role,$status,$id);
        }

        if($stmt->execute()){

            logActivity($_SESSION['user_id'], $_SESSION['role'],
                "Updated user $name ($role)"
            );

            $_SESSION['success_msg'] = "User updated successfully!";
            header("Location: manage_users.php");
            exit;

        } else {
            $error = "❌ Update failed!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit User</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="d-flex">

<?php include("../includes/sidebar.php"); ?>

<div class="content p-4 flex-grow-1">

<h3>✏️ Edit User</h3>

<a href="manage_users.php" class="btn btn-secondary mb-3">⬅ Back</a>

<?php if($error): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form method="POST" class="row g-3">

<div class="col-md-4">
<label>Name</label>
<input type="text" name="name" class="form-control" value="<?= $user['name'] ?>">
</div>

<div class="col-md-4">
<label>Email</label>
<input type="email" name="email" class="form-control" value="<?= $user['email'] ?>">
</div>

<div class="col-md-4">
<label>Role</label>
<select name="role" class="form-select">

<option value="teacher" <?= $user['role']=='teacher'?'selected':'' ?>>Teacher</option>
<option value="receptionist" <?= $user['role']=='receptionist'?'selected':'' ?>>Receptionist</option>
<option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
<option value="student" <?= $user['role']=='student'?'selected':'' ?>>Student</option>

</select>
</div>

<div class="col-md-4">
<label>Status</label>
<select name="status" class="form-select">
<option value="1" <?= $user['status']==1?'selected':'' ?>>Active</option>
<option value="0" <?= $user['status']==0?'selected':'' ?>>Inactive</option>
</select>
</div>

<div class="col-md-4">
<label>Password</label>
<input type="text" name="password" class="form-control" placeholder="New password">
</div>

<div class="col-12">
<button name="update" class="btn btn-success">Update</button>
</div>

</form>

</div>
</div>

<?php include("../includes/footer.php"); ?>
</body>
</html>