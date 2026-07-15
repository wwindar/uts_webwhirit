<?php
session_start();
require_once('db.php');
require_once('auth.php');
requireLogin();

// Include composer autoloader
require_once('vendor/autoload.php');

use Dompdf\Dompdf;
use Dompdf\Options;

$userId = $_SESSION['user_id'];

// Ambil data user
$stmtUser = $conn->prepare("SELECT nama_lengkap, username FROM users WHERE id = ?");
$stmtUser->bind_param("i", $userId);
$stmtUser->execute();
$resUser = $stmtUser->get_result();
$user = $resUser->fetch_assoc();
$namaUser = $user['nama_lengkap'] ?: $user['username'];
$stmtUser->close();

// Ambil data resensi
$query = "SELECT id, judul_buku, penulis, genre, ulasan, rating, tgl_input FROM resensi WHERE user_id = ? ORDER BY tgl_input DESC";
$st = $conn->prepare($query);
$st->bind_param("i", $userId);
$st->execute();
$result = $st->get_result();

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Resensi Buku</title>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #A35D6C;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .header p {
            margin: 0;
            color: #7f8c8d;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #fdfdfd;
        }
        .rating {
            color: #f39c12;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #95a5a6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Resensi Buku</h1>
        <p>Diekspor oleh: <strong>' . htmlspecialchars($namaUser) . '</strong> pada ' . date('d M Y H:i') . '</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th width="5%">ID</th>
                <th width="20%">Judul Buku</th>
                <th width="15%">Penulis</th>
                <th width="12%">Genre</th>
                <th width="35%">Ulasan</th>
                <th width="13%">Rating & Waktu</th>
            </tr>
        </thead>
        <tbody>';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stars = str_repeat('★', $row['rating']) . str_repeat('☆', 5 - $row['rating']);
        
        $html .= '
            <tr>
                <td>' . htmlspecialchars($row['id']) . '</td>
                <td><strong>' . htmlspecialchars($row['judul_buku']) . '</strong></td>
                <td>' . htmlspecialchars($row['penulis']) . '</td>
                <td>' . htmlspecialchars($row['genre'] ?: '-') . '</td>
                <td>' . nl2br(htmlspecialchars($row['ulasan'])) . '</td>
                <td>
                    <span class="rating">' . $stars . '</span><br>
                    <span style="font-size:10px; color:#7f8c8d;">' . date('d M Y', strtotime($row['tgl_input'])) . '</span>
                </td>
            </tr>';
    }
} else {
    $html .= '<tr><td colspan="6" style="text-align:center; padding: 20px;">Belum ada data resensi.</td></tr>';
}

$html .= '
        </tbody>
    </table>
    
    <div class="footer">
        Dihasilkan secara otomatis oleh sistem ResensiBuku.
    </div>
</body>
</html>';

$st->close();

// Konfigurasi Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// Mengatur ukuran kertas dan orientasi
$dompdf->setPaper('A4', 'landscape');

// Merender HTML sebagai PDF
$dompdf->render();

// Keluarkan PDF ke browser (force download)
$filename = "resensi_buku_" . date('Ymd_His') . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
exit();
?>
