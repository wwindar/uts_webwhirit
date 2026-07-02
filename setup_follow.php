<?php
require 'db.php';

// 1. Buat tabel pengikut
$sqlTabel = "
CREATE TABLE IF NOT EXISTS pengikut (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    pengikut_id INT(11) NOT NULL,
    diikuti_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY(pengikut_id, diikuti_id),
    FOREIGN KEY (pengikut_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (diikuti_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sqlTabel) === TRUE) {
    echo "Tabel 'pengikut' berhasil dibuat atau sudah ada.\n";
} else {
    echo "Error membuat tabel: " . $conn->error . "\n";
}

// 2. Buat beberapa akun dummy
$dummies = [
    ['username' => 'alice', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'bio' => 'Hai, saya Alice! Saya suka membaca buku fiksi ilmiah.'],
    ['username' => 'bob', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'bio' => 'Halo, saya Bob. Penggemar berat buku sejarah dan non-fiksi.'],
    ['username' => 'charlie', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'bio' => 'Charlie di sini. Sedang mencari rekomendasi novel misteri terbaru.']
];

$userIds = [];

foreach ($dummies as $user) {
    // Cek apakah username sudah ada
    $stmtCek = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmtCek->bind_param("s", $user['username']);
    $stmtCek->execute();
    $res = $stmtCek->get_result();
    
    if ($res->num_rows == 0) {
        $stmtInsert = $conn->prepare("INSERT INTO users (username, password, bio) VALUES (?, ?, ?)");
        $stmtInsert->bind_param("sss", $user['username'], $user['password'], $user['bio']);
        $stmtInsert->execute();
        $userIds[$user['username']] = $stmtInsert->insert_id;
        echo "Akun '{$user['username']}' berhasil dibuat.\n";
        $stmtInsert->close();
    } else {
        $row = $res->fetch_assoc();
        $userIds[$user['username']] = $row['id'];
        echo "Akun '{$user['username']}' sudah ada.\n";
    }
    $stmtCek->close();
}

// 3. Tambahkan beberapa relasi follow dummy
if (count($userIds) >= 3) {
    $alice = $userIds['alice'];
    $bob = $userIds['bob'];
    $charlie = $userIds['charlie'];

    $follows = [
        ['pengikut_id' => $alice, 'diikuti_id' => $bob],
        ['pengikut_id' => $bob, 'diikuti_id' => $alice],
        ['pengikut_id' => $charlie, 'diikuti_id' => $alice],
    ];

    foreach ($follows as $f) {
        $stmtF = $conn->prepare("INSERT IGNORE INTO pengikut (pengikut_id, diikuti_id) VALUES (?, ?)");
        $stmtF->bind_param("ii", $f['pengikut_id'], $f['diikuti_id']);
        $stmtF->execute();
        $stmtF->close();
    }
    echo "Relasi saling mengikuti berhasil ditambahkan.\n";
}

echo "Setup selesai!\n";
?>
