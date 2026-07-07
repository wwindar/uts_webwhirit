<?php
// likes.php — toggle like/unlike, dipanggil via POST
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$userId    = $_SESSION['user_id'];
$resensiId = intval($_POST['resensi_id'] ?? 0);
$redirect  = $_POST['redirect'] ?? "detail.php?id=$resensiId";

if ($resensiId > 0) {
    // Cek sudah like atau belum
    $st = $conn->prepare("SELECT id FROM likes WHERE resensi_id = ? AND user_id = ?");
    $st->bind_param("ii", $resensiId, $userId);
    $st->execute();
    $cek = $st->get_result();
    $sudahLike = $cek->num_rows > 0;
    $st->close();

    if ($sudahLike) {
        $st = $conn->prepare("DELETE FROM likes WHERE resensi_id = ? AND user_id = ?");
        $st->bind_param("ii", $resensiId, $userId);
        $st->execute();
        $st->close();
    } else {
        $st = $conn->prepare("INSERT INTO likes (resensi_id, user_id) VALUES (?, ?)");
        $st->bind_param("ii", $resensiId, $userId);
        $st->execute();
        $st->close();

        // Notifikasi like
        $r1 = $conn->query("SELECT user_id FROM resensi WHERE id = $resensiId");
        $pemilikResensi = $r1->fetch_assoc()['user_id'] ?? 0;
        if ($pemilikResensi > 0 && $pemilikResensi != $userId) {
            $pesan = "<b>" . htmlspecialchars($_SESSION['username']) . "</b> menyukai resensi Anda.";
            $n_stmt = $conn->prepare("INSERT INTO notifikasi (user_id, pesan, resensi_id) VALUES (?, ?, ?)");
            $n_stmt->bind_param("isi", $pemilikResensi, $pesan, $resensiId);
            $n_stmt->execute();
            $n_stmt->close();
        }
    }
}

header("Location: $redirect");
exit();