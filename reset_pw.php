<?php
require_once('db.php');

$newPassword = password_hash('1windar2', PASSWORD_DEFAULT);
$ids = [1, 2, 4, 5, 6, 7, 8, 9, 10];

foreach ($ids as $id) {
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $newPassword, $id);
    if ($stmt->execute()) {
        echo "ID $id: OK\n";
    } else {
        echo "ID $id: GAGAL\n";
    }
    $stmt->close();
}

echo "\nSelesai! Password direset ke: 1windar2\n";
$conn->close();
?>
