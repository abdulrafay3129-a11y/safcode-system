<?php
include("../config/init.php");
checkRole(['admin']);

$id = (int)($_GET['id'] ?? 0);

if(!$id){
    die("Invalid ID");
}

try{

    /* DELETE COURSE HISTORY FIRST */
    $stmt1 = $conn->prepare("DELETE FROM student_course_history WHERE student_id=?");
    $stmt1->bind_param("i",$id);
    $stmt1->execute();

    /* DELETE STUDENT */
    $stmt2 = $conn->prepare("DELETE FROM students WHERE id=?");
    $stmt2->bind_param("i",$id);

    if($stmt2->execute()){

        logActivity($_SESSION['user_id'], $_SESSION['role'], "Student permanently deleted (ID: $id)");

        header("Location: dropout_students.php?msg=deleted");
        exit;

    } else {
        echo "❌ Delete failed: " . $stmt2->error;
    }

}catch(Exception $e){
    echo "❌ Error: " . $e->getMessage();
}