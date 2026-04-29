<?php
include("../config/init.php");
checkRole(['admin']);

$message = "";

if(isset($_POST['add_teacher'])){

    $full_name = trim($_POST['full_name']);
    $email = strtolower(trim($_POST['email']));
    $contact = $_POST['contact_number'];

    $password_plain = $_POST['password'];
    $password = password_hash($password_plain, PASSWORD_DEFAULT);

    $designation = "teacher";
    $system_role = $_POST['system_role'];

    $salary_type = $_POST['salary_type'];
    $salary_amount = $_POST['salary_amount'];
    $cnic = $_POST['cnic'];
    $address = $_POST['address'];
    $joining_date = $_POST['joining_date'];

    $status = "active";

    $check = $conn->prepare("
        SELECT email FROM teachers WHERE LOWER(email)=? OR cnic=?
        UNION
        SELECT email FROM users WHERE LOWER(email)=?
    ");

    $check->bind_param("sss", $email, $cnic, $email);
    $check->execute();
    $res = $check->get_result();

    if($res->num_rows > 0){
        $message = "❌ Email ya CNIC already exist!";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO teachers 
            (full_name,email,contact_number,password,designation,salary_type,salary_amount,cnic,address,joining_date,status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "ssssssdssss",
            $full_name,
            $email,
            $contact,
            $password,
            $designation,
            $salary_type,
            $salary_amount,
            $cnic,
            $address,
            $joining_date,
            $status
        );

        if($stmt->execute()){

            $checkUser = $conn->prepare("SELECT id FROM users WHERE LOWER(email)=?");
            $checkUser->bind_param("s",$email);
            $checkUser->execute();

            if($checkUser->get_result()->num_rows == 0){

                $u = $conn->prepare("
                    INSERT INTO users (name,email,password,role,status)
                    VALUES (?,?,?,?,1)
                ");
                $u->bind_param("ssss",$full_name,$email,$password,$system_role);
                $u->execute();
            }

            logActivity($_SESSION['user_id'], $_SESSION['role'],
                "Added teacher: $full_name | Role: $system_role | Email: $email"
            );

            $message = "✅ Teacher Added Successfully!";
        } else {
            $message = "❌ Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Teacher</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* ✅ CLEAN FIX - sidebar disturb nahi karega */
.main-content {
    padding: 20px;
    width: 100%;
}

.form-box {
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.05);
}
</style>

</head>

<body>

<div class="d-flex">

    <?php include("../includes/sidebar.php"); ?>

    <div class="main-content flex-grow-1">

        <div class="form-box">

            <h3 class="mb-4">Add Teacher</h3>

            <?php if($message): ?>
                <div class="alert alert-info"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST" class="row g-3" autocomplete="off">

                <div class="col-md-4">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Contact</label>
                    <input type="text" name="contact_number" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Password</label>
                    <input type="text" name="password" class="form-control" value="123456">
                </div>

                <div class="col-md-4">
                    <label class="form-label">System Role</label>
                    <select name="system_role" class="form-select" required>
                        <option value="teacher">Teacher</option>
                        <option value="receptionist">Receptionist</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Salary Type</label>
                    <select name="salary_type" class="form-select">
                        <option value="fixed">Fixed</option>
                        <option value="hourly">Hourly</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Salary Amount</label>
                    <input type="number" name="salary_amount" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">CNIC</label>
                    <input type="text" name="cnic" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="col-md-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control"></textarea>
                </div>

                <div class="col-12">
                    <button name="add_teacher" class="btn btn-primary px-4">Add Teacher</button>
                </div>

            </form>

        </div>

    </div>

</div>

<?php include("../includes/footer.php"); ?>

</body>
</html>