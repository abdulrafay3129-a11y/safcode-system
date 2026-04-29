<?php
include("../config/init.php");
checkRole(['admin']);

$message = $_SESSION['success_msg'] ?? "";
unset($_SESSION['success_msg']);

/* AUTO SYNC USERS */

/* Deactivate users if inactive */
$conn->query("
    UPDATE users u
    LEFT JOIN teachers t ON LOWER(u.email)=LOWER(t.email)
    LEFT JOIN students s ON LOWER(u.email)=LOWER(s.email)
    SET u.status=0
    WHERE 
        (t.teacher_id IS NOT NULL AND t.status != 'active')
        OR
        (s.id IS NOT NULL AND s.status != 'active')
");

/* Reactivate teachers */
$conn->query("
    UPDATE users u
    INNER JOIN teachers t ON LOWER(u.email)=LOWER(t.email)
    SET u.status=1
    WHERE t.status='active'
");

/* Reactivate students */
$conn->query("
    UPDATE users u
    INNER JOIN students s ON LOWER(u.email)=LOWER(s.email)
    SET u.status=1
    WHERE s.status='active'
");

/* STAFF LIST */
$staff = $conn->query("
    SELECT t.full_name, t.email 
    FROM teachers t
    LEFT JOIN users u ON LOWER(t.email)=LOWER(u.email)
    WHERE t.status='active' AND u.email IS NULL
    ORDER BY t.full_name ASC
");

/* ADD USER */
if(isset($_POST['add_user'])){

    $email = strtolower(trim($_POST['email']));
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $nameQuery = $conn->prepare("SELECT full_name FROM teachers WHERE LOWER(email)=?");
    $nameQuery->bind_param("s",$email);
    $nameQuery->execute();
    $res = $nameQuery->get_result();
    $name = ($res->num_rows > 0)
        ? $res->fetch_assoc()['full_name']
        : explode("@",$email)[0];

    $check = $conn->prepare("SELECT id FROM users WHERE LOWER(email)=?");
    $check->bind_param("s",$email);
    $check->execute();

    if($check->get_result()->num_rows > 0){
        $message = "❌ User already exists!";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO users(name,email,password,role,status)
            VALUES(?,?,?,?,1)
        ");

        $stmt->bind_param("ssss",$name,$email,$password,$role);

        if($stmt->execute()){
            $_SESSION['success_msg'] = "User created successfully!";
            header("Location: manage_users.php");
            exit;
        }
    }
}

/* USERS */
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Users</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
<div class="d-flex">

<?php include("../includes/sidebar.php"); ?>

<div class="content flex-grow-1 p-4">

<h3>👥 Manage Users</h3>

<?php if($message): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- FORM -->
<div class="card p-3 mb-4 shadow-sm">
<form method="POST" class="row g-3" autocomplete="off">

<div class="col-md-5">
<label>Email / Teacher</label>
<input list="staffList" name="email" class="form-control" required>
<datalist id="staffList">
<?php while($s = $staff->fetch_assoc()): ?>
<option value="<?= $s['email'] ?>"><?= $s['full_name'] ?></option>
<?php endwhile; ?>
</datalist>
</div>

<div class="col-md-3">
<label>Role</label>
<select name="role" class="form-select" required>
<option value="">Select</option>
<option value="teacher">Teacher</option>
<option value="student">Student</option>
<option value="receptionist">Receptionist</option>
</select>
</div>

<div class="col-md-4">
<label>Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<div class="col-12">
<button class="btn btn-primary" name="add_user">➕ Assign Role</button>
</div>

</form>
</div>

<!-- TABLE -->
<div class="card p-3 shadow-sm">
<table class="table table-bordered table-hover">

<thead class="table-dark">
<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Role</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>

<tbody>
<?php while($u = $users->fetch_assoc()): ?>
<tr>
<td><?= $u['id'] ?></td>
<td><?= htmlspecialchars($u['name']) ?></td>
<td><?= htmlspecialchars($u['email']) ?></td>
<td><?= ucfirst($u['role']) ?></td>
<td>
<span class="badge bg-<?= $u['status'] ? 'success' : 'danger' ?>">
<?= $u['status'] ? 'Active' : 'Inactive' ?>
</span>
</td>
<td>
<a href="edit_user.php?id=<?= $u['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
<a href="delete_user.php?id=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
onclick="return confirm('Delete this user?')">Delete</a>
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