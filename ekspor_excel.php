<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$userId = $_SESSION['user_id'];

$composerReady = file_exists(__DIR__ . '/vendor/autoload.php');

if (!$composerReady) {
    $_SESSION['flash'] = 'Gagal mengekspor: PhpSpreadsheet belum diinstal via Composer.';
    $_SESSION['flash_type'] = 'error';
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'katalog.php';
    header("Location: $redirect");
    exit();
}

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set header
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'Judul Buku');
$sheet->setCellValue('C1', 'Penulis');
$sheet->setCellValue('D1', 'Genre');
$sheet->setCellValue('E1', 'Ulasan');
$sheet->setCellValue('F1', 'Rating');
$sheet->setCellValue('G1', 'Tanggal Input');

// Format header
$sheet->getStyle('A1:G1')->getFont()->setBold(true);

$query = "SELECT id, judul_buku, penulis, genre, ulasan, rating, tgl_input FROM resensi WHERE user_id = ?";
$st = $conn->prepare($query);
$st->bind_param("i", $userId);
$st->execute();
$result = $st->get_result();

$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $rowNum, $row['id']);
    $sheet->setCellValue('B' . $rowNum, $row['judul_buku']);
    $sheet->setCellValue('C' . $rowNum, $row['penulis']);
    $sheet->setCellValue('D' . $rowNum, $row['genre']);
    $sheet->setCellValue('E' . $rowNum, $row['ulasan']);
    $sheet->setCellValue('F' . $rowNum, $row['rating']);
    $sheet->setCellValue('G' . $rowNum, $row['tgl_input']);
    $rowNum++;
}
$st->close();

// Auto size columns
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

if (ob_get_length()) ob_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="resensi_buku_' . time() . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();