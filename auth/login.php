<?php
// Session check aur start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database aur Logger ko include karein
// InfinityFree par absolute paths use karna behtar hai
require_once(__DIR__ . "/../config/db.php");
require_once(__DIR__ . "/../config/logger.php");

$message = "";

/* 1. AGAR USER PEHLE SE LOGGED IN HAI */
if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
    $role = $_SESSION['role'];
    if ($role == 'admin') header("Location: /admin/dashboard.php");
    elseif ($role == 'teacher') header("Location: /teacher/dashboard.php");
    elseif ($role == 'student') header("Location: /student/dashboard.php");
    elseif ($role == 'receptionist') header("Location: /receptionist/dashboard.php");
    else header("Location: /index.php");
    exit;
}

/* 2. LOGIN PROCESSING */
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];

    // Database check
    if ($conn) {
        $stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if ($res && password_verify($password, $res['password'])) {
            
            if ($res['status'] != 1) {
                $message = "Account is inactive. Contact Admin.";
            } else {
                // Naya token generate karein
                $token = bin2hex(random_bytes(32));

                // Database mein token update karein
                $up = $conn->prepare("UPDATE users SET session_token=?, last_activity=NOW() WHERE id=?");
                $up->bind_param("si", $token, $res['id']);
                $up->execute();

                // Session variables set karein
                $_SESSION['user_id'] = $res['id'];
                $_SESSION['name'] = $res['name'];
                $_SESSION['role'] = $res['role'];
                $_SESSION['session_token'] = $token;

                // Log activity
                if (function_exists('logActivity')) {
                    logActivity($res['id'], $res['role'], "Login Success");
                }

                // ROLE BASED REDIRECT
                $userRole = $res['role'];
                if ($userRole == 'admin') header("Location: /admin/dashboard.php");
                elseif ($userRole == 'teacher') header("Location: /teacher/dashboard.php");
                elseif ($userRole == 'student') header("Location: /student/dashboard.php");
                elseif ($userRole == 'receptionist') header("Location: /receptionist/dashboard.php");
                else header("Location: /index.php");
                exit;
            }
        } else {
            $message = "Ghalat Email ya Password!";
        }
    } else {
        $message = "Database connection error!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SAFCODE IMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body { background: linear-gradient(135deg, #0d6efd, #6f42c1); height: 100vh; overflow: hidden; }
        .login-card { width: 380px; border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); background: #fff; }
        .btn-primary { background: #6f42c1; border: none; }
        .btn-primary:hover { background: #5a32a3; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">

<div class="card login-card p-4">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-dark">SAFCODE IMS</h3>
        <p class="text-muted">Please login your account</p>
    </div>

    <?php if($message): ?>
        <div class="alert alert-danger text-center py-2" style="font-size: 14px;"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="off">
        <div class="mb-3">
            <label class="form-label small fw-bold">Email</label>
            <input type="email" name="email" class="form-control" placeholder="Enter your Email" autocomplete="off" required>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-bold">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter your Password " autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm text-white">
            LOGIN
        </button>
    </form>

    <div class="text-center mt-4">
        <small class="text-muted">© 2024 SAFCODE Management System</small>
    </div>
</div>

</body>
</html>