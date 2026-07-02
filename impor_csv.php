<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_csv'])) {
    $file = $_FILES['file_csv'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['flash'] = 'Gagal mengunggah file. Silakan coba lagi.';
        $_SESSION['flash_type'] = 'error';
        $redirect = $_SERVER['HTTP_REFERER'] ?? 'katalog.php';
        header("Location: $redirect");
        exit();
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (strtolower($ext) !== 'csv') {
        $_SESSION['flash'] = 'Format file tidak didukung. Harus berupa .csv';
        $_SESSION['flash_type'] = 'error';
        $redirect = $_SERVER['HTTP_REFERER'] ?? 'katalog.php';
        header("Location: $redirect");
        exit();
    }

    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        $_SESSION['flash'] = 'Gagal membuka file CSV.';
        $_SESSION['flash_type'] = 'error';
        $redirect = $_SERVER['HTTP_REFERER'] ?? 'katalog.php';
        header("Location: $redirect");
        exit();
    }

    // Deteksi delimiter (koma atau titik koma)
    $firstLine = fgets($handle);
    $delimiter = ',';
    if ($firstLine !== false) {
        $commaCount = substr_count($firstLine, ',');
        $semicolonCount = substr_count($firstLine, ';');
        if ($semicolonCount > $commaCount) {
            $delimiter = ';';
        }
        rewind($handle);
    }

    $imported = 0;
    $skipped = 0;
    $rowNum = 0;

    $header = fgetcsv($handle, 1000, $delimiter);
    $colMap = [
        'judul'   => -1,
        'penulis' => -1,
        'genre'   => -1,
        'ulasan'  => -1,
        'rating'  => -1
    ];

    if ($header) {
        // Deteksi nama kolom (case insensitive)
        foreach ($header as $idx => $colName) {
            $colNameClean = strtolower(trim($colName));
            if (strpos($colNameClean, 'judul') !== false) {
                $colMap['judul'] = $idx;
            } elseif (strpos($colNameClean, 'penulis') !== false) {
                $colMap['penulis'] = $idx;
            } elseif (strpos($colNameClean, 'genre') !== false) {
                $colMap['genre'] = $idx;
            } elseif (strpos($colNameClean, 'ulasan') !== false || strpos($colNameClean, 'resensi') !== false) {
                $colMap['ulasan'] = $idx;
            } elseif (strpos($colNameClean, 'rating') !== false) {
                $colMap['rating'] = $idx;
            }
        }
    }

    // Jika kolom wajib tidak ditemukan di header, fallback ke index default (sesuai ekspor_csv.php)
    // Header ekspor: ID (0), Judul Buku (1), Penulis (2), Genre (3), Ulasan (4), Rating (5), Tanggal Input (6)
    if ($colMap['judul'] === -1) $colMap['judul'] = 1;
    if ($colMap['penulis'] === -1) $colMap['penulis'] = 2;
    if ($colMap['genre'] === -1) $colMap['genre'] = 3;
    if ($colMap['ulasan'] === -1) $colMap['ulasan'] = 4;
    if ($colMap['rating'] === -1) $colMap['rating'] = 5;

    // Masukkan data baris demi baris
    while (($data = fgetcsv($handle, 2000, $delimiter)) !== FALSE) {
        $rowNum++;

        // Pastikan baris data tidak kosong
        if (count($data) <= 1 && empty($data[0])) {
            continue;
        }

        $judul   = trim($data[$colMap['judul']] ?? '');
        $penulis = trim($data[$colMap['penulis']] ?? '');
        $genre   = trim($data[$colMap['genre']] ?? '');
        $ulasan  = trim($data[$colMap['ulasan']] ?? '');
        $rating  = intval($data[$colMap['rating']] ?? 0);

        if ($judul !== '' && $ulasan !== '') {
            $stmt = $conn->prepare(
                "INSERT INTO resensi (judul_buku, penulis, genre, ulasan, rating, user_id) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("ssssii", $judul, $penulis, $genre, $ulasan, $rating, $userId);
            if ($stmt->execute()) {
                $imported++;
            } else {
                $skipped++;
            }
            $stmt->close();
        } else {
            $skipped++;
        }
    }

    fclose($handle);

    $_SESSION['flash'] = "Berhasil mengimpor $imported resensi." . ($skipped > 0 ? " ($skipped baris dilewati)." : "");
    $_SESSION['flash_type'] = $imported > 0 ? 'success' : 'info';
}

$redirect = $_SERVER['HTTP_REFERER'] ?? 'katalog.php';
header("Location: $redirect");
exit();
