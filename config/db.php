<?php
$host = "sql202.infinityfree.com";
$user = "if0_41786405";
$pass = "CkCULLerDR";
$db   = "if0_41786405_safcode_db"; // Dashboard se confirm karo _db hai ya nahi

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
// Agar niche wali line screen par dikhe, toh samjho DB theek hai
// echo "Connected successfully"; 
?>