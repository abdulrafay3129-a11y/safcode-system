<?php
include("../config/init.php");

if(!isset($_POST['query'])) exit;

$q = "%".$_POST['query']."%";

$stmt = $conn->prepare("
    SELECT name, email 
    FROM users 
    WHERE role='teacher'
    AND (name LIKE ? OR email LIKE ?)
    LIMIT 10
");

$stmt->bind_param("ss", $q, $q);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows > 0){

    while($row = $res->fetch_assoc()){
        echo '
        <div class="email-item"
            data-email="'.$row['email'].'"
            data-name="'.$row['name'].'">

            '.$row['name'].' - '.$row['email'].'
        </div>';
    }

}else{
    echo '<div class="email-item">No teacher found</div>';
}
?>