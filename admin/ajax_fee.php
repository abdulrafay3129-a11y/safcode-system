<?php
session_start();

include("../config/ajax_init.php"); 

/* ================= SECURITY CHECK ================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "Unauthorized";
    exit;
}

/* ================= DELETE FEE ================= */
if (isset($_POST['delete_id'])) {

    $id = intval($_POST['delete_id']);

    $stmt = $conn->prepare("DELETE FROM fees WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        logActivity(
            $_SESSION['user_id'],
            $_SESSION['role'],
            "Fee deleted | ID: $id"
        );

        echo "Deleted successfully";

    } else {
        echo "Error deleting";
    }

    exit;
}
?>