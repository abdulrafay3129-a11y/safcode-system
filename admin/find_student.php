<?php
include("../config/init.php");
checkRole(['admin']);

header('Content-Type: application/json');

$code = $_GET['code'] ?? '';

if(!$code){
echo json_encode([
'success' => false,
'message' => 'No code provided'
]);
exit;
}

/*
👉 ASSUMPTION:
barcode = gr_no
*/

$stmt = $conn->prepare("SELECT id, name, gr_no FROM students WHERE gr_no=? AND status=1 LIMIT 1");
$stmt->bind_param("s", $code);
$stmt->execute();

$res = $stmt->get_result();

if($res->num_rows > 0){
$student = $res->fetch_assoc();

echo json_encode([
'success' => true,
'student' => $student
]);

}else{

echo json_encode([
'success' => false,
'message' => 'Student not found'
]);
}

exit;