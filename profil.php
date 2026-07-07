<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$pageTitle = 'Profil Saya';
$basePath = '../';
$errors = [];
$success = '';

$stmt = $conn->prepare("SELECT id, username, bio, foto_profil, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Hitung resensi milik user ini saja
$stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM resensi WHERE user_id = ?");
$stmtCount->bind_param("i", $_SESSION['user_id']);
$stmtCount->execute();
$totalResensi = $stmtCount->get_result()->fetch_assoc()['total'];
$stmtCount->close();

// Hitung pengikut (followers)
$stmtFollowers = $conn->prepare("SELECT COUNT(*) as total FROM pengikut WHERE diikuti_id = ?");
$stmtFollowers->bind_param("i", $_SESSION['user_id']);
$stmtFollowers->execute();
$totalFollowers = $stmtFollowers->get_result()->fetch_assoc()['total'];
$stmtFollowers->close();

// Hitung mengikuti (following)
$stmtFollowing = $conn->prepare("SELECT COUNT(*) as total FROM pengikut WHERE pengikut_id = ?");
$stmtFollowing->bind_param("i", $_SESSION['user_id']);
$stmtFollowing->execute();
$totalFollowing = $stmtFollowing->get_result()->fetch_assoc()['total'];
$stmtFollowing->close();

// Hitung rata-rata rating
$stmtAvg = $conn->prepare("SELECT AVG(rating) as avg_rating FROM resensi WHERE user_id = ?");
$stmtAvg->bind_param("i", $_SESSION['user_id']);
$stmtAvg->execute();
$avgRating = round($stmtAvg->get_result()->fetch_assoc()['avg_rating'] ?? 0, 1);
$stmtAvg->close();

// Genre terbanyak
$stmtGenre = $conn->prepare(
    "SELECT genre, COUNT(*) as jml FROM resensi
     WHERE user_id = ? AND genre IS NOT NULL AND genre != ''
     GROUP BY genre ORDER BY jml DESC LIMIT 1"
);
$stmtGenre->bind_param("i", $_SESSION['user_id']);
$stmtGenre->execute();
$favoriteGenre = $stmtGenre->get_result()->fetch_assoc()['genre'] ?? '-';
$stmtGenre->close();

// Ambil 5 resensi terbaru dari user ini
$stmtRecent = $conn->prepare("SELECT id, judul_buku, penulis, genre, rating, tgl_input FROM resensi WHERE user_id = ? ORDER BY tgl_input DESC LIMIT 5");
$stmtRecent->bind_param("i", $_SESSION['user_id']);
$stmtRecent->execute();
$recentReviews = $stmtRecent->get_result();
$stmtRecent->close();

if (!function_exists('renderStars')) {
    function renderStars($rating) {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            $stars .= $i <= $rating ? '★' : '☆';
        }
        return $stars;
    }
}

// Edit Profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_profil') {
    $newUsername = trim($_POST['username'] ?? '');
    $newBio = trim($_POST['bio'] ?? '');
    $fotoNama = $user['foto_profil'];

    // Cek username bentrok
    if ($newUsername !== $user['username']) {
        $stCek = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stCek->bind_param("si", $newUsername, $_SESSION['user_id']);
        $stCek->execute();
        if ($stCek->get_result()->num_rows > 0) {
            $errors[] = 'Username sudah dipakai orang lain.';
        }
        $stCek->close();
    }

    if (empty($errors)) {
        if (!empty($_FILES['foto_profil']['name'])) {
            $allowedTypes = ['image/jpeg','image/png','image/gif','image/webp'];
            $maxSize      = 2 * 1024 * 1024;
            if (!in_array($_FILES['foto_profil']['type'], $allowedTypes)) {
                $errors[] = 'Format foto profil tidak didukung.';
            } elseif ($_FILES['foto_profil']['size'] > $maxSize) {
                $errors[] = 'Ukuran foto profil maks 2 MB.';
            } else {
                $ext     = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
                $newFoto = 'profil_' . time() . '_' . rand(100,999) . '.' . $ext;
                $dir     = 'uploads/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $dir . $newFoto)) {
                    if ($fotoNama && file_exists($dir . $fotoNama)) @unlink($dir . $fotoNama);
                    $fotoNama = $newFoto;
                } else {
                    $errors[] = 'Gagal upload foto profil.';
                }
            }
        }
        
        if (isset($_POST['hapus_foto']) && $_POST['hapus_foto'] === '1') {
            $dir = 'uploads/';
            if ($fotoNama && file_exists($dir . $fotoNama)) @unlink($dir . $fotoNama);
            $fotoNama = null;
        }
    }

    if (empty($errors)) {
        $stUpdate = $conn->prepare("UPDATE users SET username=?, bio=?, foto_profil=? WHERE id=?");
        $stUpdate->bind_param("sssi", $newUsername, $newBio, $fotoNama, $_SESSION['user_id']);
        if ($stUpdate->execute()) {
            $_SESSION['username'] = $newUsername;
            $success = 'Profil berhasil diperbarui!';
            $user['username'] = $newUsername;
            $user['bio'] = $newBio;
            $user['foto_profil'] = $fotoNama;
        } else {
            $errors[] = 'Gagal memperbarui profil.';
        }
        $stUpdate->close();
    }
}

// Ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ganti_password') {
    $password_lama    = $_POST['password_lama']    ?? '';
    $password_baru    = $_POST['password_baru']    ?? '';
    $password_konfirm = $_POST['password_konfirm'] ?? '';

    if (empty($password_lama) || empty($password_baru) || empty($password_konfirm)) {
        $errors[] = 'Semua field wajib diisi.';
    } elseif (strlen($password_baru) < 6) {
        $errors[] = 'Password baru minimal 6 karakter.';
    } elseif ($password_baru !== $password_konfirm) {
        $errors[] = 'Konfirmasi password baru tidak cocok.';
    } else {
        $stmtCek = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmtCek->bind_param("i", $_SESSION['user_id']);
        $stmtCek->execute();
        $dataUser = $stmtCek->get_result()->fetch_assoc();
        $stmtCek->close();

        if (!password_verify($password_lama, $dataUser['password'])) {
            $errors[] = 'Password lama yang Anda masukkan salah.';
        } else {
            $hashedBaru = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmtUpdate = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtUpdate->bind_param("si", $hashedBaru, $_SESSION['user_id']);
            if ($stmtUpdate->execute()) {
                $success = 'Password berhasil diubah!';
            } else {
                $errors[] = 'Gagal mengubah password. Silakan coba lagi.';
            }
            $stmtUpdate->close();
        }
    }
}

// Cek apakah Composer/PhpSpreadsheet tersedia
$composerReady = file_exists(__DIR__ . '/vendor/autoload.php');
?>
<?php include ('header.php'); ?>

<div class="main-content">
    <div class="page-header">
        <h1>👤 Profil Saya</h1>
        <p>Informasi akun dan pengaturan keamanan.</p>
    </div>

    <div class="profil-grid" style="display:grid;gap:1.5rem;align-items:start">

        <!-- Kolom Kiri -->
        <div style="display:flex;flex-direction:column;gap:1.5rem">
            <!-- ── Kartu Info Profil ── -->
            <div style="background:var(--paper);border:1px solid var(--border);border-top:3px solid var(--gold);
                        border-radius:4px;padding:1.8rem;box-shadow:0 4px 20px var(--shadow)">

            <div style="text-align:center;margin-bottom:1.5rem">
                <?php if (!empty($user['foto_profil']) && file_exists('uploads/' . $user['foto_profil'])): ?>
                    <img src="uploads/<?= htmlspecialchars($user['foto_profil']) ?>" alt="Foto Profil"
                         style="width:80px;height:80px;border-radius:50%;object-fit:cover;
                                margin:0 auto 0.75rem;border:3px solid var(--gold);box-shadow:0 2px 10px rgba(0,0,0,0.1);display:block">
                <?php else: ?>
                    <div style="width:72px;height:72px;background:var(--ink);border-radius:50%;
                                display:flex;align-items:center;justify-content:center;
                                margin:0 auto 0.75rem;font-size:2rem;border:3px solid var(--gold)">👤</div>
                <?php endif; ?>
                <h2 style="font-family:var(--font-display);font-size:1.3rem;color:var(--ink)">
                    <?= htmlspecialchars($user['username']) ?>
                </h2>
                <span style="font-size:0.78rem;color:var(--brown);background:rgba(212,168,67,0.12);
                             border:1px solid rgba(212,168,67,0.3);border-radius:20px;padding:0.2rem 0.75rem">
                    Member
                </span>
            </div>

            <div style="border-top:1px solid var(--border);padding-top:1rem">
                <?php if (!empty($user['bio'])): ?>
                <div style="margin-bottom:1.25rem;text-align:center">
                    <p style="font-size:0.9rem;color:var(--ink);line-height:1.5;font-style:italic">
                        "<?= nl2br(htmlspecialchars($user['bio'])) ?>"
                    </p>
                </div>
                <?php endif; ?>
                
                <div style="margin-bottom:0.9rem">
                    <div style="font-size:0.75rem;font-weight:500;color:var(--ink-light);
                                text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem">Username</div>
                    <div style="font-size:0.95rem;color:var(--ink);font-weight:500">
                        <?= htmlspecialchars($user['username']) ?>
                    </div>
                </div>
                <div style="margin-bottom:0.9rem">
                    <div style="font-size:0.75rem;font-weight:500;color:var(--ink-light);
                                text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem">Bergabung Sejak</div>
                    <div style="font-size:0.95rem;color:var(--ink)">
                        <?= date('d F Y', strtotime($user['created_at'])) ?>
                    </div>
                </div>

                <!-- Statistik -->
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;margin-bottom:0.9rem">
                    <a href="#recent-resensi" style="text-decoration:none; color:inherit; display:block">
                        <div style="background:var(--page-bg);border-radius:8px;padding:0.75rem;text-align:center;transition:transform 0.2s;cursor:pointer" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            <div style="font-size:1.5rem;font-family:var(--font-display);color:var(--gold);
                                        font-weight:700;line-height:1"><?= $totalResensi ?></div>
                            <div style="font-size:0.72rem;color:var(--ink-light);margin-top:0.2rem">Resensi</div>
                        </div>
                    </a>
                    <div style="background:var(--page-bg);border-radius:8px;padding:0.75rem;text-align:center;transition:transform 0.2s;cursor:pointer" onclick="openKoneksiModal('pengikut', <?= $_SESSION['user_id'] ?>)" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <div style="font-size:1.5rem;font-family:var(--font-display);color:var(--gold);
                                    font-weight:700;line-height:1" id="follower-count"><?= $totalFollowers ?></div>
                        <div style="font-size:0.72rem;color:var(--ink-light);margin-top:0.2rem">Pengikut</div>
                    </div>
                    <div style="background:var(--page-bg);border-radius:8px;padding:0.75rem;text-align:center;transition:transform 0.2s;cursor:pointer" onclick="openKoneksiModal('mengikuti', <?= $_SESSION['user_id'] ?>)" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <div style="font-size:1.5rem;font-family:var(--font-display);color:var(--gold);
                                    font-weight:700;line-height:1" id="following-count"><?= $totalFollowing ?></div>
                        <div style="font-size:0.72rem;color:var(--ink-light);margin-top:0.2rem">Mengikuti</div>
                    </div>
                </div>
                <div style="margin-bottom:0.9rem">
                    <div style="font-size:0.75rem;font-weight:500;color:var(--ink-light);
                                text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem">Rata-rata Rating (Resensi)</div>
                    <div style="font-size:0.95rem;color:var(--ink)">
                        <?= $avgRating > 0 ? $avgRating : '-' ?> ★
                    </div>
                </div>
                <div style="margin-bottom:0.9rem">
                    <div style="font-size:0.75rem;font-weight:500;color:var(--ink-light);
                                text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem">Genre Favorit</div>
                    <div style="font-size:0.95rem;color:var(--ink)"><?= htmlspecialchars($favoriteGenre) ?></div>
                </div>
            </div>

            <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:0.75rem">
                <button onclick="document.getElementById('modal-edit-profil').style.display='block';document.body.style.overflow='hidden'" class="btn btn-gold btn-full">
                    ✏️ Edit Profil
                </button>
                <a href="katalog.php" class="btn btn-outline btn-full" style="text-align:center;display:block">
                    📚 Lihat Katalog
                </a>
            </div>
        </div>

            <!-- ── Resensi Terbaru ── -->
            <div id="recent-resensi" style="background:var(--paper);border:1px solid var(--border);border-top:3px solid #3498db;
                        border-radius:4px;padding:1.8rem;box-shadow:0 4px 20px var(--shadow)">
                <h2 style="font-family:var(--font-display);font-size:1.2rem;color:var(--ink);margin-bottom:1rem">
                    📚 Resensi Terbaru Saya
                </h2>

                <?php if ($recentReviews->num_rows === 0): ?>
                    <div class="empty-state" style="padding:2rem">
                        <div class="empty-icon">📭</div>
                        <h3>Belum ada resensi</h3>
                        <p>Anda belum menulis ulasan apapun.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Judul Buku</th>
                                    <th>Rating</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $recentReviews->fetch_assoc()): ?>
                                <tr>
                                    <td class="td-title" style="font-size:0.95rem">
                                        <?= htmlspecialchars($row['judul_buku']) ?><br>
                                        <small style="color:var(--ink-light);font-weight:normal"><?= htmlspecialchars($row['penulis']) ?></small>
                                    </td>
                                    <td class="td-rating"><?= renderStars($row['rating']) ?></td>
                                    <td>
                                        <a href="detail.php?id=<?= $row['id'] ?>" class="btn btn-outline btn-sm">Lihat</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Kolom kanan: Ganti Password + Ekspor ── -->
        <div style="display:flex;flex-direction:column;gap:1.5rem">

            <!-- Ganti Password -->
            <div style="background:var(--paper);border:1px solid var(--border);border-top:3px solid var(--gold);
                        border-radius:4px;padding:1.8rem;box-shadow:0 4px 20px var(--shadow)">
                <h2 style="font-family:var(--font-display);font-size:1.2rem;color:var(--ink);margin-bottom:0.3rem">
                    🔒 Ganti Password
                </h2>
                <p style="color:var(--ink-light);font-size:0.85rem;margin-bottom:1.4rem;
                           padding-bottom:1rem;border-bottom:1px solid var(--border)">
                    Pastikan password baru minimal 6 karakter.
                </p>

                <?php if ($errors): ?>
                <div class="alert alert-error">
                    <strong>Perhatikan:</strong><br>
                    <?php foreach ($errors as $e): ?>
                        • <?= htmlspecialchars($e) ?><br>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="ganti_password">
                    <div class="form-group">
                        <label for="password_lama">Password Lama <span style="color:#c0392b">*</span></label>
                        <input type="password" id="password_lama" name="password_lama"
                            placeholder="Masukkan password saat ini" required>
                    </div>
                    <div class="form-group">
                        <label for="password_baru">Password Baru <span style="color:#c0392b">*</span></label>
                        <input type="password" id="password_baru" name="password_baru"
                            placeholder="Min. 6 karakter" required>
                    </div>
                    <div class="form-group">
                        <label for="password_konfirm">Konfirmasi Password Baru <span style="color:#c0392b">*</span></label>
                        <input type="password" id="password_konfirm" name="password_konfirm"
                            placeholder="Ulangi password baru" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Password Baru</button>
                </form>
            </div>

            <!-- ── EKSPOR DATA ── -->
            <div style="background:var(--paper);border:1px solid var(--border);border-top:3px solid #27ae60;
                        border-radius:4px;padding:1.8rem;box-shadow:0 4px 20px var(--shadow)">
                <h2 style="font-family:var(--font-display);font-size:1.2rem;color:var(--ink);margin-bottom:0.3rem">
                    📤 Ekspor Data Resensi
                </h2>
                <p style="color:var(--ink-light);font-size:0.85rem;margin-bottom:1.25rem;
                           padding-bottom:1rem;border-bottom:1px solid var(--border)">
                    Unduh seluruh resensi yang kamu tulis dalam format spreadsheet.
                </p>

                <!-- Ekspor CSV (selalu tersedia) -->
                <div style="background:var(--page-bg);border-radius:8px;padding:1rem;margin-bottom:0.85rem;
                            border:1px solid var(--border)">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
                        <div>
                            <div style="font-weight:600;font-size:0.95rem">📄 Format CSV</div>
                            <div style="font-size:0.78rem;color:var(--ink-light);margin-top:0.2rem">
                                Bisa dibuka di Excel, Google Sheets, LibreOffice. Tidak butuh Composer.
                            </div>
                        </div>
                        <a href="ekspor_csv.php" class="btn btn-outline btn-sm"
                           style="white-space:nowrap;border-color:#27ae60;color:#27ae60">
                            ⬇ Unduh CSV
                        </a>
                    </div>
                </div>

                <!-- Ekspor Excel -->
                <div style="background:var(--page-bg);border-radius:8px;padding:1rem;margin-bottom:0.85rem;
                            border:1px solid var(--border)">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
                        <div>
                            <div style="font-weight:600;font-size:0.95rem">📊 Format Excel (.xlsx)</div>
                            <div style="font-size:0.78rem;color:var(--ink-light);margin-top:0.2rem">
                                File Excel dengan format warna & tabel rapi. Butuh PhpSpreadsheet via Composer.
                            </div>
                            <?php if (!$composerReady): ?>
                            <div style="font-size:0.75rem;color:#e67e22;margin-top:0.4rem;
                                        background:#fef9e7;padding:0.35rem 0.6rem;border-radius:4px;
                                        border:1px solid #f39c12;display:inline-block">
                                ⚠️ Composer belum disetup —
                                <a href="#panduan-composer" onclick="document.getElementById('panduan-composer').style.display='block'"
                                   style="color:#e67e22;font-weight:600">lihat panduan</a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <a href="ekspor_excel.php"
                           class="btn btn-sm <?= $composerReady ? '' : 'btn-outline' ?>"
                           style="white-space:nowrap;<?= $composerReady ? 'background:#27ae60;color:#fff;border-color:#27ae60' : 'color:#aaa;border-color:#ccc' ?>">
                            ⬇ Unduh Excel
                        </a>
                    </div>
                </div>

                <!-- Ekspor Word -->
                <div style="background:var(--page-bg);border-radius:8px;padding:1rem;
                            border:1px solid var(--border)">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
                        <div>
                            <div style="font-weight:600;font-size:0.95rem">📝 Format Word (.doc)</div>
                            <div style="font-size:0.78rem;color:var(--ink-light);margin-top:0.2rem">
                                Bisa dibuka di Microsoft Word, Google Docs, dll. Format tabel sederhana.
                            </div>
                        </div>
                        <a href="ekspor_word.php" class="btn btn-outline btn-sm"
                           style="white-space:nowrap;border-color:#2980b9;color:#2980b9">
                            ⬇ Unduh Word
                        </a>
                    </div>
                </div>

                <!-- Panduan Composer (tersembunyi, muncul jika belum setup) -->
                <?php if (!$composerReady): ?>
                <div id="panduan-composer" style="display:none;margin-top:1rem;background:#f8f9fa;
                            border-radius:8px;padding:1.1rem;border:1px solid #dee2e6;font-size:0.85rem">
                    <strong>📦 Cara install PhpSpreadsheet:</strong>
                    <ol style="margin:0.6rem 0 0 1.2rem;line-height:1.9">
                        <li>Buka <strong>Terminal</strong> di VSCode (<code>Ctrl + `</code>)</li>
                        <li>Pastikan sudah di folder proyek: <code>cd C:\xampp\htdocs\uts_webwhirit</code></li>
                        <li>Jalankan:
                            <code style="display:block;background:#2C3E50;color:#ECF0F1;padding:0.5rem 0.75rem;
                                         border-radius:4px;margin:0.3rem 0;font-size:0.88rem">
                                composer require phpoffice/phpspreadsheet
                            </code>
                        </li>
                        <li>Tunggu sampai selesai, lalu refresh halaman ini</li>
                    </ol>
                    <p style="margin:0.6rem 0 0;color:var(--ink-light)">
                        Belum punya Composer? Download di
                        <a href="https://getcomposer.org/download/" target="_blank">getcomposer.org</a>
                    </p>
                </div>
                <?php endif; ?>

            </div>

            <!-- ── IMPOR DATA ── -->
            <div style="background:var(--paper);border:1px solid var(--border);border-top:3px solid #2980b9;
                        border-radius:4px;padding:1.8rem;box-shadow:0 4px 20px var(--shadow)">
                <h2 style="font-family:var(--font-display);font-size:1.2rem;color:var(--ink);margin-bottom:0.3rem">
                    📥 Impor Data Resensi
                </h2>
                <p style="color:var(--ink-light);font-size:0.85rem;margin-bottom:1.25rem;
                           padding-bottom:1rem;border-bottom:1px solid var(--border)">
                    Unggah file CSV untuk menambahkan resensi secara masal.
                </p>

                <form method="POST" action="impor_csv.php" enctype="multipart/form-data">
                    <div class="form-group" style="margin-bottom:1rem">
                        <label>Pilih File CSV <span style="color:#c0392b">*</span></label>
                        <input type="file" name="file_csv" accept=".csv" required style="padding:0.4rem 0">
                        <small style="color:var(--ink-light);font-size:0.78rem;display:block;margin-top:0.3rem">
                            Kolom minimum: <strong>Judul Buku</strong> dan <strong>Ulasan</strong>. Format harus sesuai (bisa gunakan hasil Ekspor CSV sebagai template).
                        </small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full" style="background:#2980b9;border-color:#2980b9">
                        ⬆ Unggah & Impor CSV
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<!-- ══════════════ MODAL EDIT PROFIL ══════════════ -->
<div id="modal-edit-profil" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;
     overflow-y:auto;padding:2rem 1rem" onclick="if(event.target===this){this.style.display='none';document.body.style.overflow=''}">
    <div style="background:#fff;border-radius:16px;max-width:500px;margin:auto;padding:2rem;position:relative">
        <button onclick="document.getElementById('modal-edit-profil').style.display='none';document.body.style.overflow=''" style="position:absolute;top:1rem;right:1rem;
            background:none;border:none;font-size:1.4rem;cursor:pointer;color:#888;line-height:1">×</button>
        <h2 style="font-family:var(--font-head);font-size:1.3rem;margin-bottom:1.5rem">✏️ Edit Profil</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_profil">
            
            <div class="form-group">
                <label>FOTO PROFIL</label>
                <?php if (!empty($user['foto_profil']) && file_exists('uploads/' . $user['foto_profil'])): ?>
                <div style="margin-bottom:0.75rem;display:flex;align-items:center;gap:1rem">
                    <img src="uploads/<?= htmlspecialchars($user['foto_profil']) ?>" alt="Foto" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--border)">
                    <label style="font-size:0.85rem;cursor:pointer">
                        <input type="checkbox" name="hapus_foto" value="1"> Hapus foto saat ini
                    </label>
                </div>
                <?php endif; ?>
                <input type="file" name="foto_profil" accept="image/jpeg,image/png,image/gif,image/webp">
                <small style="color:var(--ink-light);font-size:0.78rem">JPG, PNG, GIF, WEBP — maks 2 MB</small>
            </div>
            
            <div class="form-group">
                <label>USERNAME <span style="color:#c0392b">*</span></label>
                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required maxlength="50">
            </div>
            
            <div class="form-group">
                <label>BIO / CATATAN PROFIL</label>
                <textarea name="bio" placeholder="Ceritakan sedikit tentang dirimu..." style="min-height:100px" maxlength="500"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                <small style="color:var(--ink-light);font-size:0.78rem">Maksimal 500 karakter.</small>
            </div>
            
            <div style="display:flex;gap:0.75rem;margin-top:0.5rem">
                <button type="submit" class="btn btn-primary">💾 Simpan Profil</button>
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-edit-profil').style.display='none';document.body.style.overflow=''">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════ MODAL KONEKSI (FOLLOWER/FOLLOWING) ══════════════ -->
<div id="modal-koneksi" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;
     overflow-y:auto;padding:2rem 1rem" onclick="if(event.target===this){this.style.display='none';document.body.style.overflow=''}">
    <div style="background:#fff;border-radius:16px;max-width:400px;margin:auto;padding:1.5rem;position:relative">
        <button onclick="document.getElementById('modal-koneksi').style.display='none';document.body.style.overflow=''" style="position:absolute;top:1rem;right:1rem;
            background:none;border:none;font-size:1.4rem;cursor:pointer;color:#888;line-height:1">×</button>
        <h2 id="modal-koneksi-title" style="font-family:var(--font-head);font-size:1.2rem;margin-bottom:1rem;text-align:center">Daftar</h2>
        
        <div id="modal-koneksi-body" style="max-height:60vh;overflow-y:auto;padding-right:0.5rem">
            <div style="text-align:center;color:#888;padding:2rem">Memuat...</div>
        </div>
    </div>
</div>

<script>
function openKoneksiModal(type, userId) {
    const modal = document.getElementById('modal-koneksi');
    const title = document.getElementById('modal-koneksi-title');
    const body = document.getElementById('modal-koneksi-body');
    
    title.innerText = type === 'pengikut' ? 'Pengikut' : 'Mengikuti';
    body.innerHTML = '<div style="text-align:center;color:#888;padding:2rem">Memuat...</div>';
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    fetch(`get_koneksi.php?type=${type}&user_id=${userId}`)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            body.innerHTML = '';
            if (data.data.length === 0) {
                body.innerHTML = '<div style="text-align:center;color:#888;padding:2rem">Belum ada data.</div>';
                return;
            }
            
            data.data.forEach(user => {
                const item = document.createElement('div');
                item.style.display = 'flex';
                item.style.alignItems = 'center';
                item.style.justifyContent = 'space-between';
                item.style.padding = '0.75rem 0';
                item.style.borderBottom = '1px solid var(--border)';
                
                let imgHtml = '';
                if (user.foto_profil) {
                    imgHtml = `<img src="uploads/${user.foto_profil}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:1px solid var(--border)">`;
                } else {
                    imgHtml = `<div style="width:40px;height:40px;border-radius:50%;background:var(--gold);color:white;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:1.1rem">${user.username.charAt(0).toUpperCase()}</div>`;
                }
                
                let btnHtml = '';
                if (!user.is_me) {
                    const btnClass = user.is_following ? 'btn-outline' : 'btn-primary';
                    const btnText = user.is_following ? 'Berhenti' : 'Ikuti';
                    btnHtml = `<button class="btn ${btnClass} btn-sm" onclick="toggleFollow(this, ${user.id})">${btnText}</button>`;
                }
                
                item.innerHTML = `
                    <div style="display:flex;align-items:center;gap:0.75rem">
                        ${imgHtml}
                        <a href="profil_publik.php?id=${user.id}" style="font-weight:600;color:var(--ink);text-decoration:none">${user.username}</a>
                    </div>
                    <div>
                        ${btnHtml}
                    </div>
                `;
                body.appendChild(item);
            });
        } else {
            body.innerHTML = `<div style="text-align:center;color:#e74c3c;padding:2rem">${data.message}</div>`;
        }
    })
    .catch(err => {
        body.innerHTML = '<div style="text-align:center;color:#e74c3c;padding:2rem">Gagal memuat data.</div>';
        console.error(err);
    });
}

function toggleFollow(btn, userId) {
    const isFollowing = btn.classList.contains('btn-outline');
    const action = isFollowing ? 'unfollow' : 'follow';
    
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('diikuti_id', userId);
    formData.append('action', action);
    
    fetch('follow_action.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            if(action === 'follow') {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline');
                btn.innerText = 'Berhenti';
            } else {
                btn.classList.remove('btn-outline');
                btn.classList.add('btn-primary');
                btn.innerText = 'Ikuti';
            }
            // Note: we don't dynamically update the stats count on the main page here 
            // to keep it simple, but we could if we wanted.
        } else {
            alert(data.message);
        }
    })
    .catch(err => console.error(err))
    .finally(() => {
        btn.disabled = false;
    });
}
</script>

<?php include ('footer.php'); ?>