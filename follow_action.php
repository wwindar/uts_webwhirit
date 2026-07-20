<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$pengikut_id = $_SESSION['user_id'];
$diikuti_id = isset($_POST['diikuti_id']) ? intval($_POST['diikuti_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($diikuti_id <= 0 || $pengikut_id == $diikuti_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
    exit();
}

if ($action === 'follow') {
    $stmt = $conn->prepare("INSERT IGNORE INTO pengikut (pengikut_id, diikuti_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $pengikut_id, $diikuti_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $pesan = "<b>" . htmlspecialchars($_SESSION['username']) . "</b> mulai mengikuti Anda.";
            require_once 'notify.php';
            createNotification($diikuti_id, 'follow', ['message' => $pesan]);
        }
        echo json_encode(['status' => 'success', 'message' => 'Berhasil mengikuti']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengikuti']);
    }
    $stmt->close();

} elseif ($action === 'unfollow') {
    $stmt = $conn->prepare("DELETE FROM pengikut WHERE pengikut_id = ? AND diikuti_id = ?");
    $stmt->bind_param("ii", $pengikut_id, $diikuti_id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Berhasil batal mengikuti']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal batal mengikuti']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>
