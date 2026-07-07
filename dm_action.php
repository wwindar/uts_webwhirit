<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

if ($action === 'fetch') {
    $user_id = intval($_GET['user_id'] ?? 0);
    $last_id = intval($_GET['last_id'] ?? 0);
    
    if ($user_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
        exit();
    }
    
    $sql = "SELECT id, pengirim_id, penerima_id, isi_pesan, created_at 
            FROM pesan 
            WHERE id > ? AND ((pengirim_id = ? AND penerima_id = ?) OR (pengirim_id = ? AND penerima_id = ?))
            ORDER BY id ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiii", $last_id, $current_user_id, $user_id, $user_id, $current_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $messages = [];
    while($row = $res->fetch_assoc()){
        $messages[] = $row;
    }
    $stmt->close();
    
    // Tandai sudah dibaca
    $stUpdate = $conn->prepare("UPDATE pesan SET dibaca = 1 WHERE pengirim_id = ? AND penerima_id = ? AND dibaca = 0");
    $stUpdate->bind_param("ii", $user_id, $current_user_id);
    $stUpdate->execute();
    $stUpdate->close();
    
    echo json_encode(['status' => 'success', 'messages' => $messages]);
    exit();
}
elseif ($action === 'send') {
    $penerima_id = intval($_POST['penerima_id'] ?? 0);
    $isi_pesan = trim($_POST['isi_pesan'] ?? '');
    
    if ($penerima_id <= 0 || empty($isi_pesan)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO pesan (pengirim_id, penerima_id, isi_pesan) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $current_user_id, $penerima_id, $isi_pesan);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim pesan']);
    }
    $stmt->close();
    exit();
}
else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>
