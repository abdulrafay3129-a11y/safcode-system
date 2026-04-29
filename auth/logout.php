<?php
include("../config/init.php");
include("../config/logger.php");

if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], $_SESSION['role'], "Manual logout");
}

session_unset();
session_destroy();

header("Location: ../auth/login.php");
exit;