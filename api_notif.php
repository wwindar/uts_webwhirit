<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$uid = $_SESSION['user_id'];
$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

$stmt = $conn->prepare("SELECT id, pesan, created_at FROM notifikasi WHERE user_id = ? AND id > ? ORDER BY id ASC LIMIT 20");
$stmt->bind_param("ii", $uid, $last_id);
$stmt->execute();
$res = $stmt->get_result();

$notifs = [];
while ($row = $res->fetch_assoc()) {
    $notifs[] = $row;
}
$stmt->close();

echo json_encode([
    'status' => 'success',
    'notifs' => $notifs
]);
?>
