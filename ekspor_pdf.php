<?php
session_start();
require_once('db.php');
require_once('auth.php');
requireLogin();

// Check if composer autoloader and Dompdf are available
$composerReady = file_exists(__DIR__ . '/vendor/autoload.php');
if ($composerReady) {
    require_once(__DIR__ . '/vendor/autoload.php');
}

$useDompdf = $composerReady && class_exists('Dompdf\Dompdf');

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

if ($useDompdf) {
    // Generate PDF menggunakan Dompdf (sama seperti semula)
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

    $options = new \Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Helvetica');

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $filename = "resensi_buku_" . date('Ymd_His') . ".pdf";
    $dompdf->stream($filename, ["Attachment" => true]);
    exit();

} else {
    // Tampilkan versi HTML cetak premium jika Dompdf belum terinstal (di server hosting gratis)
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Laporan Resensi Buku - Print Preview</title>
        <style>
            body {
                font-family: Helvetica, Arial, sans-serif;
                color: #333;
                margin: 20px;
                padding: 0;
                background-color: #f8f9fa;
            }
            .print-container {
                max-width: 1000px;
                margin: 0 auto;
                background: #fff;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #A35D6C;
                padding-bottom: 15px;
            }
            .header h1 {
                color: #2c3e50;
                margin: 0 0 10px 0;
                font-size: 26px;
            }
            .header p {
                margin: 0;
                color: #7f8c8d;
                font-size: 14px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
                margin-bottom: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 10px;
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
                font-size: 14px;
            }
            .footer {
                margin-top: 30px;
                text-align: right;
                font-size: 11px;
                color: #95a5a6;
                border-top: 1px solid #eee;
                padding-top: 10px;
            }
            /* Style untuk floating button */
            .btn-print-container {
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #fff;
                padding: 15px 20px;
                margin: 0 auto 20px auto;
                max-width: 1000px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                border: 1px solid #e9ecef;
            }
            .btn-print {
                background-color: #A35D6C;
                color: white;
                border: none;
                padding: 10px 20px;
                font-size: 14px;
                cursor: pointer;
                border-radius: 6px;
                font-weight: bold;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                box-shadow: 0 2px 4px rgba(163, 93, 108, 0.2);
                transition: background 0.2s;
            }
            .btn-print:hover {
                background-color: #8c4e5c;
            }
            .btn-back {
                color: #6c757d;
                text-decoration: none;
                font-size: 14px;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            .btn-back:hover {
                color: #333;
                text-decoration: underline;
            }
            @media print {
                body {
                    background-color: #fff;
                    margin: 0;
                    padding: 0;
                }
                .print-container {
                    box-shadow: none;
                    padding: 0;
                    max-width: 100%;
                }
                .no-print {
                    display: none !important;
                }
            }
        </style>
    </head>
    <body>
        <div class="btn-print-container no-print">
            <a href="katalog.php" class="btn-back">← Kembali ke Katalog</a>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="font-size: 13px; color: #666;">Tips: Pilih <strong>"Simpan sebagai PDF" / "Save as PDF"</strong> di tujuan printer Anda.</span>
                <button onclick="window.print()" class="btn-print">🖨️ Cetak / Simpan PDF</button>
            </div>
        </div>

        <div class="print-container">
            <div class="header">
                <h1>Laporan Resensi Buku</h1>
                <p>Diekspor oleh: <strong><?php echo htmlspecialchars($namaUser); ?></strong> pada <?php echo date('d M Y H:i'); ?></p>
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
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $stars = str_repeat('★', $row['rating']) . str_repeat('☆', 5 - $row['rating']);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['judul_buku']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['penulis']); ?></td>
                                <td><?php echo htmlspecialchars($row['genre'] ?: '-'); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($row['ulasan'])); ?></td>
                                <td>
                                    <span class="rating"><?php echo $stars; ?></span><br>
                                    <span style="font-size:10px; color:#7f8c8d;"><?php echo date('d M Y', strtotime($row['tgl_input'])); ?></span>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="6" style="text-align:center; padding: 20px;">Belum ada data resensi.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
            
            <div class="footer">
                Dihasilkan secara otomatis oleh sistem ResensiBuku.
            </div>
        </div>

        <script>
            // Otomatis buka dialog print setelah halaman termuat
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    </body>
    </html>
    <?php
    $st->close();
    exit();
}
?>
