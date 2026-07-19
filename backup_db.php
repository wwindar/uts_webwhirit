<?php
session_start();
require_once('db.php');
require_once('auth.php');

// Fitur backup hanya boleh diakses oleh Admin
requireAdmin();

// Set header untuk download file
$filename = "backup_uts_web_" . date("Y-m-d_H-i-s") . ".sql";
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Ambil daftar semua tabel
$tables = array();
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

$sqlDump = "-- Database Backup\n";
$sqlDump .= "-- Waktu Backup: " . date("Y-m-d H:i:s") . "\n\n";

// Loop untuk setiap tabel
foreach ($tables as $table) {
    // Dapatkan struktur tabel (CREATE TABLE)
    $result = $conn->query("SHOW CREATE TABLE `$table`");
    $row = $result->fetch_row();
    $sqlDump .= "\n\n-- Struktur dari tabel `$table`\n";
    $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
    $sqlDump .= $row[1] . ";\n\n";
    
    // Dapatkan data tabel (INSERT INTO)
    $result = $conn->query("SELECT * FROM `$table`");
    $numRows = $result->num_rows;
    $numFields = $result->field_count;
    
    if ($numRows > 0) {
        $sqlDump .= "-- Data dari tabel `$table`\n";
        
        while ($row = $result->fetch_row()) {
            $sqlDump .= "INSERT INTO `$table` VALUES(";
            for ($j = 0; $j < $numFields; $j++) {
                if (isset($row[$j])) {
                    $escaped = $conn->real_escape_string($row[$j]);
                    $sqlDump .= "'$escaped'";
                } else {
                    $sqlDump .= "NULL";
                }
                
                if ($j < ($numFields - 1)) {
                    $sqlDump .= ", ";
                }
            }
            $sqlDump .= ");\n";
        }
    }
}

// Output isi dump langsung ke browser
echo $sqlDump;

$conn->close();
exit();
?>