<?php
require_once('db.php');

header('Content-Type: application/json');

$username = strtolower(trim($_GET['username'] ?? ''));

// Validasi format
if (!preg_match('/^[a-z0-9_.]+$/', $username)) {
    echo json_encode(['status' => 'invalid', 'message' => 'Hanya boleh huruf kecil, angka, titik (.), dan garis bawah (_).']);
    exit;
}
if (strlen($username) < 4) {
    echo json_encode(['status' => 'invalid', 'message' => 'Username minimal 4 karakter.']);
    exit;
}

// Cek apakah sudah dipakai
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Username sudah dipakai — buat rekomendasi
    $suggestions = [];
    $baseUsername = preg_replace('/[0-9_]+$/', '', $username);
    if (empty($baseUsername)) $baseUsername = $username;

    for ($i = 0; $i < 10 && count($suggestions) < 5; $i++) {
        $candidate = $baseUsername . '_' . rand(1, 999);
        $stCheck = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stCheck->bind_param("s", $candidate);
        $stCheck->execute();
        if ($stCheck->get_result()->num_rows === 0 && !in_array($candidate, $suggestions)) {
            $suggestions[] = $candidate;
        }
        $stCheck->close();
    }

    $candidates2 = [
        $baseUsername . date('y'),
        $baseUsername . '_' . date('Y'),
        $baseUsername . '.' . rand(10, 99),
    ];
    foreach ($candidates2 as $c) {
        if (count($suggestions) >= 5) break;
        $stCheck = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stCheck->bind_param("s", $c);
        $stCheck->execute();
        if ($stCheck->get_result()->num_rows === 0 && !in_array($c, $suggestions)) {
            $suggestions[] = $c;
        }
        $stCheck->close();
    }

    echo json_encode([
        'status' => 'taken',
        'message' => 'Username sudah dipakai.',
        'suggestions' => array_slice($suggestions, 0, 5)
    ]);
} else {
    echo json_encode(['status' => 'available', 'message' => 'Username tersedia!']);
}

$stmt->close();
$conn->close();
?>
