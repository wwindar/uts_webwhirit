<?php
require 'db.php';
$email = "test@example.com";
$nomor_telepon = "";
$username = "hhongshi10";
$password = "test";
$nama = "Hong";
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$insert = $conn->prepare("INSERT INTO users (username, nama_lengkap, email, nomor_telepon, password, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
$insert->bind_param("sssss", $username, $nama, $email, $nomor_telepon, $hashedPassword);
if(!$insert->execute()){
    echo "Error: " . $insert->error;
} else {
    echo "Success";
}
?>
