<?php

session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resensiId = intval($_POST['resensi_id'] ?? 0);
    $action    = $_POST['action'] ?? '';

    if ($action === 'tambah') {
        $isi = trim($_POST['isi'] ?? '');
        if ($resensiId > 0 && $isi !== '') {
            $st = $conn->prepare("INSERT INTO komentar (resensi_id, user_id, isi_komentar) VALUES (?, ?, ?)");
            $st->bind_param("iis", $resensiId, $userId, $isi);
            $st->execute();
            $st->close();

            // Notifikasi komentar using shared helper
            require_once 'notify.php';
            $r1 = $conn->query("SELECT user_id FROM resensi WHERE id = $resensiId");
            $pemilikResensi = $r1->fetch_assoc()['user_id'] ?? 0;
            if ($pemilikResensi > 0 && $pemilikResensi != $userId) {
                $pesan = "<b>" . htmlspecialchars($_SESSION['username']) . "</b> mengomentari resensi Anda: <i>\"" . htmlspecialchars(substr($isi, 0, 30)) . (strlen($isi) > 30 ? "..." : "") . "\"</i>";
                createNotification($pemilikResensi, 'comment', ['message' => $pesan, 'resensi_id' => $resensiId]);
            }

            $_SESSION['flash'] = 'Komentar berhasil ditambahkan!';
            $_SESSION['flash_type'] = 'success';
        }
        header("Location: detail.php?id=$resensiId#komentar");
        exit();

    } elseif ($action === 'hapus') {
        $kid = intval($_POST['komentar_id'] ?? 0);
        // pokoknya hapus komentar punya user ini aja, biar aman
        $st = $conn->prepare("DELETE FROM komentar WHERE id = ? AND user_id = ?");
        $st->bind_param("ii", $kid, $userId);
        $st->execute();
        $st->close();
        $_SESSION['flash'] = 'Komentar dihapus.';
        $_SESSION['flash_type'] = 'info';
        header("Location: detail.php?id=$resensiId#komentar");
        exit();
    }
}

header("Location: katalog.php");
exit();