<?php
require 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS pesan (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    pengirim_id INT(11) NOT NULL,
    penerima_id INT(11) NOT NULL,
    isi_pesan TEXT NOT NULL,
    dibaca TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pengirim_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (penerima_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Tabel 'pesan' berhasil dibuat atau sudah ada.\n";
} else {
    echo "Error membuat tabel: " . $conn->error . "\n";
}
?>
