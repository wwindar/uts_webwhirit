<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$user_id = intval($_GET['user_id'] ?? 0);
$type = $_GET['type'] ?? '';

if ($user_id <= 0 || !in_array($type, ['pengikut', 'mengikuti'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit();
}

$users = [];

if ($type === 'pengikut') {
    // Siapa yang mengikuti user_id
    $sql = "SELECT u.id, u.username, u.foto_profil,
            (SELECT COUNT(*) FROM pengikut p2 WHERE p2.pengikut_id = ? AND p2.diikuti_id = u.id) as is_following
            FROM users u
            JOIN pengikut p ON p.pengikut_id = u.id
            WHERE p.diikuti_id = ?
            ORDER BY p.created_at DESC";
} else {
    // Siapa yang diikuti oleh user_id
    $sql = "SELECT u.id, u.username, u.foto_profil,
            (SELECT COUNT(*) FROM pengikut p2 WHERE p2.pengikut_id = ? AND p2.diikuti_id = u.id) as is_following
            FROM users u
            JOIN pengikut p ON p.diikuti_id = u.id
            WHERE p.pengikut_id = ?
            ORDER BY p.created_at DESC";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $current_user_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $row['is_me'] = ($row['id'] == $current_user_id) ? 1 : 0;
    $users[] = $row;
}
$stmt->close();

echo json_encode(['status' => 'success', 'data' => $users]);
?>
