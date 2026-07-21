<?php
session_start();
require_once('db.php');
require_once('auth.php');
requireLogin();

$pageTitle = 'Pengaturan';
$uid = $_SESSION['user_id'];
$errors = [];
$success = '';

// Ambil data user
$stmt = $conn->prepare("SELECT id, username, nama_lengkap, bio, email, nomor_telepon, foto_profil, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Handle POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 0. Update Kontak (Email & Nomor Telepon)
    if ($action === 'update_kontak') {
        $email = trim($_POST['email'] ?? '');
        $nomor_telepon = trim($_POST['nomor_telepon'] ?? '');

        if (empty($email)) {
            $errors[] = 'Email tidak boleh kosong.';
        } else {
            // Cek apakah email sudah dipakai user lain
            $stmtCek = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmtCek->bind_param("si", $email, $uid);
            $stmtCek->execute();
            $resultCek = $stmtCek->get_result();
            if ($resultCek->num_rows > 0) {
                $errors[] = 'Email sudah digunakan oleh akun lain.';
            }
            $stmtCek->close();

            // Cek apakah nomor telepon sudah dipakai user lain (jika diisi)
            if (!empty($nomor_telepon)) {
                $stmtCekTlp = $conn->prepare("SELECT id FROM users WHERE nomor_telepon = ? AND id != ?");
                $stmtCekTlp->bind_param("si", $nomor_telepon, $uid);
                $stmtCekTlp->execute();
                $resultCekTlp = $stmtCekTlp->get_result();
                if ($resultCekTlp->num_rows > 0) {
                    $errors[] = 'Nomor telepon sudah digunakan oleh akun lain.';
                }
                $stmtCekTlp->close();
            }
        }

        if (empty($errors)) {
            $stmtUp = $conn->prepare("UPDATE users SET email = ?, nomor_telepon = ? WHERE id = ?");
            $stmtUp->bind_param("ssi", $email, $nomor_telepon, $uid);
            if ($stmtUp->execute()) {
                $success = 'Kontak berhasil disimpan!';
                // Refresh data user
                $user['email'] = $email;
                $user['nomor_telepon'] = $nomor_telepon;
            } else {
                $errors[] = 'Gagal menyimpan kontak.';
            }
            $stmtUp->close();
        }
    }

    // 1. Ganti Password
    if ($action === 'ganti_password') {
        $pass_lama    = $_POST['password_lama']    ?? '';
        $pass_baru    = $_POST['password_baru']    ?? '';
        $pass_konfirm = $_POST['password_konfirm'] ?? '';

        if (empty($pass_lama) || empty($pass_baru) || empty($pass_konfirm)) {
            $errors[] = 'Semua kolom password wajib diisi.';
        } elseif (strlen($pass_baru) < 6) {
            $errors[] = 'Password baru minimal 6 karakter.';
        } elseif ($pass_baru !== $pass_konfirm) {
            $errors[] = 'Konfirmasi password tidak cocok.';
        } else {
            $stCek = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stCek->bind_param("i", $uid);
            $stCek->execute();
            $row = $stCek->get_result()->fetch_assoc();
            $stCek->close();
            if (!password_verify($pass_lama, $row['password'])) {
                $errors[] = 'Password lama salah.';
            } else {
                $hashed = password_hash($pass_baru, PASSWORD_DEFAULT);
                $stUp   = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stUp->bind_param("si", $hashed, $uid);
                $stUp->execute();
                $stUp->close();
                $success = 'Password berhasil diubah!';
            }
        }
    }

    // 2. Hapus Akun
    if ($action === 'hapus_akun') {
        $konfirmasi = $_POST['konfirmasi_hapus'] ?? '';
        if ($konfirmasi !== 'HAPUS') {
            $errors[] = 'Ketik HAPUS untuk konfirmasi penghapusan akun.';
        } else {
            $conn->query("DELETE FROM notifikasi WHERE user_id = $uid");
            $conn->query("DELETE FROM pesan WHERE pengirim_id = $uid OR penerima_id = $uid");
            $conn->query("DELETE FROM pengikut WHERE pengikut_id = $uid OR diikuti_id = $uid");
            $conn->query("DELETE FROM resensi WHERE user_id = $uid");
            $conn->query("DELETE FROM users WHERE id = $uid");
            session_destroy();
            header('Location: index.php?hapus=1');
            exit;
        }
    }
}
?>
<?php include('header.php'); ?>

<style>
/* ============ PENGATURAN PAGE ============ */
.settings-wrapper {
    max-width: 820px;
    margin: 2.5rem auto;
    padding: 0 1.5rem 4rem;
    flex: 1;
    width: 100%;
    box-sizing: border-box;
}

@media (max-width: 768px) {
    .settings-wrapper {
        margin: 1rem auto;
        padding: 0 1rem 3rem;
    }
}

.settings-header {
    margin-bottom: 2rem;
}
.settings-header h1 {
    font-family: var(--font-display);
    font-size: 2rem;
    color: var(--ink);
    margin-bottom: 0.25rem;
}
.settings-header p {
    color: var(--ink-light);
    font-size: 0.9rem;
}

/* Tabs */
.settings-tabs {
    display: flex;
    gap: 0.4rem;
    border-bottom: 2px solid var(--border);
    margin-bottom: 2rem;
    overflow-x: auto;
    padding-bottom: 2px;
    scrollbar-width: none;
}
.settings-tabs::-webkit-scrollbar { display: none; }
.settings-tab-btn {
    background: none;
    border: none;
    padding: 0.6rem 1.1rem;
    font-family: var(--font-body);
    font-size: 0.88rem;
    font-weight: 500;
    color: var(--ink-light);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    border-radius: 8px 8px 0 0;
    white-space: nowrap;
    transition: color 0.2s, border-color 0.2s;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.settings-tab-btn:hover { color: var(--ink); }
.settings-tab-btn.active {
    color: var(--rose);
    border-bottom-color: var(--rose);
    font-weight: 600;
}

/* Tab Panels */
.settings-panel { display: none; }
.settings-panel.active {
    display: block;
    animation: fadeInPanel 0.22s ease;
}
@keyframes fadeInPanel {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Card */
.settings-card {
    background: var(--paper);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 1.8rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 12px var(--shadow);
}
.settings-card-title {
    font-family: var(--font-display);
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding-bottom: 0.8rem;
    border-bottom: 1px solid var(--border);
}

/* Setting Row */
.setting-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.9rem 0;
    border-bottom: 1px solid var(--border);
    gap: 1rem;
}
.setting-row:last-child { border-bottom: none; }
.setting-label {
    font-weight: 500;
    font-size: 0.9rem;
    color: var(--ink);
}
.setting-desc {
    font-size: 0.78rem;
    color: var(--ink-light);
    margin-top: 2px;
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}
.toggle-switch input { opacity:0; width:0; height:0; position:absolute; }
.toggle-track {
    position: absolute;
    inset: 0;
    background: var(--border);
    border-radius: 24px;
    cursor: pointer;
    transition: background 0.25s;
}
.toggle-track::before {
    content: '';
    position: absolute;
    width: 18px; height: 18px;
    background: white;
    border-radius: 50%;
    top: 3px; left: 3px;
    transition: transform 0.25s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.15);
}
.toggle-switch input:checked + .toggle-track { background: var(--rose); }
.toggle-switch input:checked + .toggle-track::before { transform: translateX(20px); }

/* Theme Selector */
.theme-options { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 0.5rem; }
.theme-option {
    display: flex; flex-direction: column; align-items: center;
    gap: 0.4rem; cursor: pointer;
}
.theme-option input[type="radio"] { display: none; }
.theme-swatch {
    width: 64px; height: 44px;
    border-radius: 10px;
    border: 2px solid var(--border);
    transition: border-color 0.2s, transform 0.2s;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
}
.theme-swatch.light-swatch { background: #ffffff; }
.theme-swatch.dark-swatch  { background: #1E1E1E; }
.theme-swatch.sepia-swatch { background: #f5ead2; }
.theme-option input:checked + .theme-swatch {
    border-color: var(--rose);
    transform: scale(1.08);
}
.theme-option-label { font-size: 0.75rem; color: var(--ink-light); font-weight: 500; }

/* Font Slider */
.font-slider-wrap {
    display: flex; align-items: center; gap: 0.75rem;
    width: 100%; max-width: 300px;
}
.font-slider-wrap input[type="range"] { flex: 1; accent-color: var(--rose); }
.font-size-preview { font-size: 0.8rem; color: var(--ink-light); min-width: 40px; text-align: right; }

/* Password */
.pw-form { display: flex; flex-direction: column; gap: 1rem; }
.pw-form .form-group { margin-bottom: 0; }
.pw-input-wrap { position: relative; }
.pw-input-wrap input { padding-right: 2.8rem; }
.pw-toggle-eye {
    position: absolute; right: 0.75rem; top: 50%;
    transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: var(--ink-light); font-size: 1rem; padding: 0; line-height: 1;
}

/* Info Row */
.info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 0.75rem 0; border-bottom: 1px solid var(--border); font-size: 0.9rem;
}
.info-row:last-child { border-bottom: none; }
.info-row-key { color: var(--ink-light); font-weight: 500; }
.info-row-val { color: var(--ink); font-weight: 500; }

/* Danger */
.danger-card { border-color: #e74c3c44; border-top: 3px solid #e74c3c; }
.danger-card .settings-card-title { color: #c0392b; }
.danger-input {
    width: 100%; padding: 0.65rem 0.9rem;
    border: 1.5px solid #e74c3c88; border-radius: 10px;
    background: var(--paper); color: var(--ink);
    font-family: var(--font-body); font-size: 0.9rem;
    margin-top: 0.5rem; transition: border-color 0.2s;
}
.danger-input:focus { outline: none; border-color: #e74c3c; }
.btn-danger-hard {
    background: #e74c3c; color: white; border: none;
    padding: 0.6rem 1.5rem; border-radius: 20px;
    font-family: var(--font-body); font-weight: 600; font-size: 0.88rem;
    cursor: pointer; transition: background 0.2s, transform 0.2s; margin-top: 0.75rem;
}
.btn-danger-hard:hover { background: #c0392b; transform: translateY(-1px); }

/* Alert */
.settings-alert {
    padding: 0.8rem 1.1rem; border-radius: 10px; margin-bottom: 1.2rem;
    font-size: 0.88rem; border-left: 3px solid;
    animation: fadeInPanel 0.3s ease;
}
.settings-alert.success { background: var(--sage-light); border-color: #27ae60; color: #1e8449; }
.settings-alert.error   { background: var(--rose-light); border-color: var(--rose-dark); color: var(--rose-deep); }

/* Stats grid */
.stats-mini-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.5rem;
}
.stats-mini-item {
    border-radius: 12px; padding: 1rem; text-align: center;
}
.stats-mini-num { font-family: var(--font-display); font-size: 2rem; font-weight: 700; line-height: 1; }
.stats-mini-lbl { font-size: 0.75rem; color: var(--ink-light); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem; }
</style>

<div class="settings-wrapper">

    <div class="settings-header">
        <h1>⚙️ Pengaturan</h1>
        <p>Kelola preferensi tampilan, keamanan akun, dan privasi kamu.</p>
    </div>

    <?php if ($success): ?>
        <div class="settings-alert success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="settings-alert error">
            <?php foreach ($errors as $e): ?>⚠️ <?= htmlspecialchars($e) ?><br><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- TABS -->
    <div class="settings-tabs">
        <button class="settings-tab-btn active" data-tab="tampilan">🎨 Tampilan</button>
        <button class="settings-tab-btn" data-tab="akun">👤 Akun</button>
        <button class="settings-tab-btn" data-tab="keamanan">🔒 Keamanan</button>
        <button class="settings-tab-btn" data-tab="notifikasi">🔔 Notifikasi</button>
        <button class="settings-tab-btn" data-tab="bahaya">🗑️ Hapus Akun</button>
    </div>

    <!-- ===== TAB TAMPILAN ===== -->
    <div class="settings-panel active" id="tab-tampilan">

        <div class="settings-card">
            <div class="settings-card-title">🖌️ Tema Aplikasi</div>

            <!-- Toggle Mode Gelap / Terang -->
            <div class="setting-row">
                <div>
                    <div class="setting-label" id="darkModeLabel">Mode Gelap</div>
                    <div class="setting-desc">Aktifkan tampilan gelap untuk kenyamanan mata di malam hari.</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="darkModeToggle">
                    <span class="toggle-track"></span>
                </label>
            </div>

            <!-- Pilihan Tema Lengkap -->
            <p style="font-size:0.82rem;color:var(--ink-light);margin:0.75rem 0 0.5rem;">Atau pilih tema:</p>
            <div class="theme-options">
                <label class="theme-option">
                    <input type="radio" name="theme_pick" value="light" id="themeLight">
                    <div class="theme-swatch light-swatch">☀️</div>
                    <span class="theme-option-label">Terang</span>
                </label>
                <label class="theme-option">
                    <input type="radio" name="theme_pick" value="dark" id="themeDark">
                    <div class="theme-swatch dark-swatch">🌙</div>
                    <span class="theme-option-label">Gelap</span>
                </label>
                <label class="theme-option">
                    <input type="radio" name="theme_pick" value="sepia" id="themeSepia">
                    <div class="theme-swatch sepia-swatch">📖</div>
                    <span class="theme-option-label">Sepia</span>
                </label>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-title">🔤 Ukuran Teks</div>
            <div class="setting-row" style="border:none;padding-top:0;">
                <div>
                    <div class="setting-label">Besar Huruf</div>
                    <div class="setting-desc">Sesuaikan ukuran teks untuk kenyamanan membaca.</div>
                </div>
                <div class="font-slider-wrap">
                    <span style="font-size:0.75rem;color:var(--ink-light);">A</span>
                    <input type="range" id="fontSizeSlider" min="13" max="20" value="15" step="1">
                    <span style="font-size:1rem;color:var(--ink-light);">A</span>
                    <span class="font-size-preview" id="fontSizeLabel">15px</span>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-title">✨ Animasi & Efek</div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Animasi Halaman</div>
                    <div class="setting-desc">Aktifkan efek transisi antar elemen.</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="toggleAnimasi" checked>
                    <span class="toggle-track"></span>
                </label>
            </div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Hover Effects</div>
                    <div class="setting-desc">Tampilkan efek saat kursor diarahkan ke elemen.</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="toggleHover" checked>
                    <span class="toggle-track"></span>
                </label>
            </div>
        </div>

    </div><!-- /tab-tampilan -->

    <!-- ===== TAB AKUN ===== -->
    <div class="settings-panel" id="tab-akun">
        <div class="settings-card">
            <div class="settings-card-title">👤 Informasi Akun</div>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_kontak">
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="font-weight: 500; font-size: 0.85rem; color: var(--ink);">Username</label>
                    <input type="text" value="@<?= htmlspecialchars($user['username']) ?>" disabled style="background: var(--cream); cursor: not-allowed; border: 1px solid var(--border);">
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="email" style="font-weight: 500; font-size: 0.85rem; color: var(--ink);">Alamat Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="contoh@gmail.com" required style="border: 1px solid var(--border); border-radius: 8px; padding: 0.5rem; width: 100%; box-sizing: border-box; background: var(--paper); color: var(--ink);">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="nomor_telepon" style="font-weight: 500; font-size: 0.85rem; color: var(--ink);">Nomor Telepon / WA</label>
                    <input type="text" id="nomor_telepon" name="nomor_telepon" value="<?= htmlspecialchars($user['nomor_telepon'] ?? '') ?>" placeholder="08xxxxxxxxxx" style="border: 1px solid var(--border); border-radius: 8px; padding: 0.5rem; width: 100%; box-sizing: border-box; background: var(--paper); color: var(--ink);">
                </div>

                <button type="submit" class="btn btn-primary btn-sm">💾 Simpan Kontak</button>
            </form>

            <hr style="border: none; border-top: 1px solid var(--border); margin: 1.5rem 0 1rem 0;">
            
            <div class="info-row">
                <span class="info-row-key">Nama Lengkap</span>
                <span class="info-row-val"><?= htmlspecialchars($user['nama_lengkap'] ?: '-') ?></span>
            </div>
            <div class="info-row">
                <span class="info-row-key">Bergabung Sejak</span>
                <span class="info-row-val"><?= date('d M Y', strtotime($user['created_at'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-row-key">Bio</span>
                <span class="info-row-val" style="text-align:right;max-width:260px;"><?= htmlspecialchars($user['bio'] ?: '-') ?></span>
            </div>
            <div style="margin-top:1rem;padding-top:0.75rem;border-top:1px solid var(--border);">
                <a href="profil.php" class="btn btn-outline btn-sm">✏️ Edit Profil & Bio Lengkap</a>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-title">🔗 Tautan Cepat</div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Profil Publik</div>
                    <div class="setting-desc">Lihat tampilan profil seperti yang dilihat orang lain.</div>
                </div>
                <a href="profil_publik.php?user=<?= urlencode($user['username']) ?>" class="btn btn-outline btn-sm">Lihat →</a>
            </div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Wishlist</div>
                    <div class="setting-desc">Kelola daftar buku yang ingin kamu baca.</div>
                </div>
                <a href="wishlist.php" class="btn btn-outline btn-sm">Buka →</a>
            </div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Bookmark</div>
                    <div class="setting-desc">Buku-buku yang sudah kamu tandai.</div>
                </div>
                <a href="bookmarks.php" class="btn btn-outline btn-sm">Buka →</a>
            </div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Kotak Masuk (DM)</div>
                    <div class="setting-desc">Pesan langsung dari sesama pengguna.</div>
                </div>
                <a href="dm.php" class="btn btn-outline btn-sm">Buka →</a>
            </div>
        </div>
    </div><!-- /tab-akun -->

    <!-- ===== TAB KEAMANAN ===== -->
    <div class="settings-panel" id="tab-keamanan">
        <div class="settings-card">
            <div class="settings-card-title">🔑 Ganti Password</div>
            <form method="POST" class="pw-form" autocomplete="off">
                <input type="hidden" name="action" value="ganti_password">
                <div class="form-group">
                    <label for="password_lama">Password Lama</label>
                    <div class="pw-input-wrap">
                        <input type="password" id="password_lama" name="password_lama" placeholder="Password saat ini" required>
                        <button type="button" class="pw-toggle-eye" onclick="togglePw('password_lama',this)">👁️</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password_baru">Password Baru</label>
                    <div class="pw-input-wrap">
                        <input type="password" id="password_baru" name="password_baru" placeholder="Minimal 6 karakter" required>
                        <button type="button" class="pw-toggle-eye" onclick="togglePw('password_baru',this)">👁️</button>
                    </div>
                    <div id="pwStrengthBar" style="height:4px;border-radius:4px;background:var(--border);margin-top:6px;overflow:hidden;">
                        <div id="pwStrengthFill" style="height:100%;width:0%;border-radius:4px;transition:width 0.3s,background 0.3s;"></div>
                    </div>
                    <p id="pwStrengthLabel" style="font-size:0.73rem;color:var(--ink-light);margin-top:2px;"></p>
                </div>
                <div class="form-group">
                    <label for="password_konfirm">Konfirmasi Password Baru</label>
                    <div class="pw-input-wrap">
                        <input type="password" id="password_konfirm" name="password_konfirm" placeholder="Ulangi password baru" required>
                        <button type="button" class="pw-toggle-eye" onclick="togglePw('password_konfirm',this)">👁️</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">💾 Simpan Password</button>
            </form>
        </div>

        <div class="settings-card">
            <div class="settings-card-title">🛡️ Sesi & Keamanan</div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Status Login</div>
                    <div class="setting-desc">Kamu sedang masuk sebagai <strong>@<?= htmlspecialchars($user['username']) ?></strong></div>
                </div>
                <span style="padding:0.25rem 0.75rem;background:#eafaf1;color:#1e8449;border-radius:20px;font-size:0.78rem;font-weight:600;">● Aktif</span>
            </div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Keluar Sekarang</div>
                    <div class="setting-desc">Akhiri sesi login kamu di perangkat ini.</div>
                </div>
                <a href="logout.php" class="btn btn-outline btn-sm" style="color:#e74c3c;border-color:#e74c3c;">🚪 Keluar</a>
            </div>
        </div>
    </div><!-- /tab-keamanan -->

    <!-- ===== TAB NOTIFIKASI ===== -->
    <div class="settings-panel" id="tab-notifikasi">
        <div class="settings-card">
            <div class="settings-card-title">🔔 Preferensi Notifikasi</div>
            <p style="font-size:0.85rem;color:var(--ink-light);margin-bottom:1rem;">Preferensi ini disimpan di browser kamu.</p>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Notifikasi Like</div>
                    <div class="setting-desc">Tampilkan pop-up ketika resensimu disukai.</div>
                </div>
                <label class="toggle-switch"><input type="checkbox" id="notifLike" checked><span class="toggle-track"></span></label>
            </div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Notifikasi Komentar</div>
                    <div class="setting-desc">Tampilkan pop-up ketika ada komentar baru.</div>
                </div>
                <label class="toggle-switch"><input type="checkbox" id="notifKomentar" checked><span class="toggle-track"></span></label>
            </div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Notifikasi Pengikut Baru</div>
                    <div class="setting-desc">Tampilkan pop-up ketika ada yang mengikutimu.</div>
                </div>
                <label class="toggle-switch"><input type="checkbox" id="notifFollow" checked><span class="toggle-track"></span></label>
            </div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Notifikasi Pesan (DM)</div>
                    <div class="setting-desc">Tampilkan pop-up ketika ada pesan masuk.</div>
                </div>
                <label class="toggle-switch"><input type="checkbox" id="notifDm" checked><span class="toggle-track"></span></label>
            </div>
            <div style="margin-top:1rem;padding-top:0.75rem;border-top:1px solid var(--border);">
                <a href="notifikasi.php" class="btn btn-outline btn-sm">📋 Lihat Semua Notifikasi</a>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-title">📊 Ringkasan Aktivitas</div>
            <?php
            $stRes = $conn->prepare("SELECT COUNT(*) as c FROM resensi WHERE user_id = ?");
            $stRes->bind_param("i",$uid); $stRes->execute();
            $cntRes = $stRes->get_result()->fetch_assoc()['c']; $stRes->close();

            $stWish = $conn->prepare("SELECT COUNT(*) as c FROM wishlist WHERE user_id = ?");
            $stWish->bind_param("i",$uid); $stWish->execute();
            $cntWish = $stWish->get_result()->fetch_assoc()['c']; $stWish->close();

            $cntBook = 0;
            if ($conn->query("SHOW TABLES LIKE 'bookmarks'")->num_rows > 0) {
                $stBook = $conn->prepare("SELECT COUNT(*) as c FROM bookmarks WHERE user_id = ?");
                $stBook->bind_param("i",$uid); $stBook->execute();
                $cntBook = $stBook->get_result()->fetch_assoc()['c']; $stBook->close();
            }

            $stUnread = $conn->prepare("SELECT COUNT(*) as c FROM notifikasi WHERE user_id = ? AND sudah_dibaca = 0");
            $stUnread->bind_param("i",$uid); $stUnread->execute();
            $cntUnread = $stUnread->get_result()->fetch_assoc()['c']; $stUnread->close();
            ?>
            <div class="stats-mini-grid">
                <div class="stats-mini-item" style="background:var(--rose-light);">
                    <div class="stats-mini-num" style="color:var(--rose);"><?= $cntRes ?></div>
                    <div class="stats-mini-lbl">Resensi</div>
                </div>
                <div class="stats-mini-item" style="background:var(--sage-light);">
                    <div class="stats-mini-num" style="color:var(--sage-dark);"><?= $cntWish ?></div>
                    <div class="stats-mini-lbl">Wishlist</div>
                </div>
                <div class="stats-mini-item" style="background:var(--sage-light);">
                    <div class="stats-mini-num" style="color:var(--sage-dark);"><?= $cntBook ?></div>
                    <div class="stats-mini-lbl">Bookmark</div>
                </div>
                <div class="stats-mini-item" style="background:var(--rose-light);">
                    <div class="stats-mini-num" style="color:var(--rose);"><?= $cntUnread ?></div>
                    <div class="stats-mini-lbl">Notif Belum Dibaca</div>
                </div>
            </div>
        </div>
    </div><!-- /tab-notifikasi -->

    <!-- ===== TAB HAPUS AKUN ===== -->
    <div class="settings-panel" id="tab-bahaya">
        <div class="settings-card danger-card">
            <div class="settings-card-title">⚠️ Zona Berbahaya</div>
            <p style="font-size:0.9rem;color:var(--ink);margin-bottom:1rem;">
                Tindakan di bawah ini bersifat <strong>permanen dan tidak bisa dibatalkan</strong>. Pastikan kamu benar-benar yakin.
            </p>
            <div class="setting-row">
                <div>
                    <div class="setting-label" style="color:#c0392b;">🗑️ Hapus Seluruh Akun</div>
                    <div class="setting-desc">Semua data kamu (resensi, pesan, notifikasi, pengikut) akan dihapus permanen.</div>
                </div>
                <button type="button" class="btn-danger-hard" id="btnShowHapus" onclick="document.getElementById('hapusAkunForm').style.display='block';this.style.display='none';">
                    Hapus Akun
                </button>
            </div>

            <form method="POST" id="hapusAkunForm" style="display:none;margin-top:1rem;padding-top:1rem;border-top:1px dashed #e74c3c88;" onsubmit="return konfirmasiHapus()">
                <input type="hidden" name="action" value="hapus_akun">
                <label style="font-size:0.85rem;color:#c0392b;font-weight:600;display:block;margin-bottom:0.4rem;">
                    Ketik <strong>HAPUS</strong> untuk konfirmasi:
                </label>
                <input type="text" name="konfirmasi_hapus" class="danger-input" placeholder="Ketik HAPUS di sini" id="inputHapus" autocomplete="off">
                <br>
                <button type="submit" class="btn-danger-hard">🗑️ Ya, Hapus Akun Saya</button>
                <button type="button" style="margin-left:0.5rem;background:none;border:none;cursor:pointer;font-size:0.85rem;color:var(--ink-light);" onclick="document.getElementById('hapusAkunForm').style.display='none';document.getElementById('btnShowHapus').style.display='';">
                    Batalkan
                </button>
            </form>
        </div>
    </div><!-- /tab-bahaya -->

</div><!-- /.settings-wrapper -->

<?php include('footer.php'); ?>

<script>
// ===== TABS =====
document.querySelectorAll('.settings-tab-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.settings-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
        localStorage.setItem('settings_tab', btn.dataset.tab);
    });
});
(function(){
    var lastTab = localStorage.getItem('settings_tab');
    if (lastTab) { var b = document.querySelector('[data-tab="'+lastTab+'"]'); if (b) b.click(); }
})();

// ===== THEME PICKER + DARK MODE TOGGLE =====
function applyTheme(t) {
    document.documentElement.removeAttribute('style');
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('theme', t);
    // sync radio
    var r = document.querySelector('[name="theme_pick"][value="'+t+'"]');
    if (r) r.checked = true;
    // sync toggle switch
    var tog = document.getElementById('darkModeToggle');
    if (tog) tog.checked = (t === 'dark');
    // update label
    var lbl = document.getElementById('darkModeLabel');
    if (lbl) lbl.textContent = t === 'dark' ? '🌙 Mode Gelap (Aktif)' : '🌙 Mode Gelap';
}

(function(){
    applyTheme(localStorage.getItem('theme') || 'light');

    // Radio buttons
    document.querySelectorAll('[name="theme_pick"]').forEach(function(radio){
        radio.addEventListener('change', function(){ applyTheme(this.value); });
    });

    // Toggle switch (gelap ↔ terang)
    var tog = document.getElementById('darkModeToggle');
    if (tog) {
        tog.addEventListener('change', function(){
            applyTheme(this.checked ? 'dark' : 'light');
        });
    }
})();

// ===== FONT SLIDER =====
(function(){
    var slider = document.getElementById('fontSizeSlider');
    var label  = document.getElementById('fontSizeLabel');
    var saved  = localStorage.getItem('font_size') || '15';
    slider.value = saved;
    label.textContent = saved + 'px';
    document.body.style.fontSize = saved + 'px';
    slider.addEventListener('input', function(){
        var sz = this.value;
        label.textContent = sz + 'px';
        document.body.style.fontSize = sz + 'px';
        localStorage.setItem('font_size', sz);
    });
})();

// ===== TOGGLES =====
['toggleAnimasi','toggleHover','notifLike','notifKomentar','notifFollow','notifDm'].forEach(function(id){
    var el = document.getElementById(id);
    if (!el) return;
    if (localStorage.getItem('pref_'+id) === 'false') el.checked = false;
    el.addEventListener('change', function(){ localStorage.setItem('pref_'+id, this.checked); });
});

// ===== PW TOGGLE =====
function togglePw(fieldId, btn) {
    var inp = document.getElementById(fieldId);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.textContent = inp.type === 'text' ? '🙈' : '👁️';
}

// ===== PW STRENGTH =====
var pwBaru = document.getElementById('password_baru');
if (pwBaru) {
    pwBaru.addEventListener('input', function(){
        var pw = this.value, s = 0;
        if (pw.length >= 6)  s++;
        if (pw.length >= 10) s++;
        if (/[A-Z]/.test(pw)) s++;
        if (/[0-9]/.test(pw)) s++;
        if (/[^A-Za-z0-9]/.test(pw)) s++;
        var fill = document.getElementById('pwStrengthFill');
        var lbl  = document.getElementById('pwStrengthLabel');
        var colors = ['#e74c3c','#e67e22','#f1c40f','#2ecc71','#27ae60'];
        var labels = ['Sangat Lemah','Lemah','Cukup','Kuat','Sangat Kuat'];
        fill.style.width = (s*20)+'%';
        fill.style.background = colors[s-1] || '#e74c3c';
        lbl.textContent = pw ? (labels[s-1]||'') : '';
    });
}

// ===== CONFIRM DELETE =====
function konfirmasiHapus() {
    if (document.getElementById('inputHapus').value !== 'HAPUS') {
        alert('Ketik persis "HAPUS" untuk melanjutkan.'); return false;
    }
    return confirm('Ini akan menghapus akun kamu secara permanen. Lanjutkan?');
}
</script>
