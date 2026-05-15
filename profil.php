<?php

session_start();
require_once ('db.php');
require_once ('auth.php');

requireLogin();

$pageTitle = 'Profil Saya';
$basePath = '../';

$errors = [];
$success = '';

$stmt = $conn->prepare("SELECT id, username, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM resensi");
$stmtCount->execute();
$totalResensi = $stmtCount->get_result()->fetch_assoc()['total'];
$stmtCount->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_lama   = $_POST['password_lama'] ?? '';
    $password_baru   = $_POST['password_baru'] ?? '';
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
?>
<?php include ('header.php'); ?>

<div class="main-content">
    <div class="page-header">
        <h1>👤 Profil Saya</h1>
        <p>Informasi akun dan pengaturan keamanan.</p>
    </div>

    <div class="profil-grid" style="display:grid;gap:1.5rem;align-items:start">

        <div style="background:var(--paper);border:1px solid var(--border);border-top:3px solid var(--gold);border-radius:4px;padding:1.8rem;box-shadow:0 4px 20px var(--shadow)">
            <div style="text-align:center;margin-bottom:1.5rem">
                <div style="width:72px;height:72px;background:var(--ink);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;font-size:2rem;border:3px solid var(--gold)">
                    👤
                </div>
                <h2 style="font-family:var(--font-display);font-size:1.3rem;color:var(--ink)">
                    <?= htmlspecialchars($user['username']) ?>
                </h2>
                <span style="font-size:0.78rem;color:var(--brown);background:rgba(212,168,67,0.12);border:1px solid rgba(212,168,67,0.3);border-radius:20px;padding:0.2rem 0.75rem">
                    Member
                </span>
            </div>

            <div style="border-top:1px solid var(--border);padding-top:1rem">
                <div style="margin-bottom:0.9rem">
                    <div style="font-size:0.75rem;font-weight:500;color:var(--ink-light);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem">Username</div>
                    <div style="font-size:0.95rem;color:var(--ink);font-weight:500"><?= htmlspecialchars($user['username']) ?></div>
                </div>
                <div style="margin-bottom:0.9rem">
                    <div style="font-size:0.75rem;font-weight:500;color:var(--ink-light);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem">Bergabung Sejak</div>
                    <div style="font-size:0.95rem;color:var(--ink)"><?= date('d F Y', strtotime($user['created_at'])) ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem;font-weight:500;color:var(--ink-light);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem">Total Resensi Ditulis</div>
                    <div style="font-size:1.8rem;font-family:var(--font-display);color:var(--gold);font-weight:700;line-height:1"><?= $totalResensi ?></div>
                </div>
            </div>

            <div style="margin-top:1.2rem;padding-top:1rem;border-top:1px solid var(--border)">
                <a href="katalog.php" class="btn btn-outline btn-full" style="text-align:center">📚 Lihat Katalog</a>
            </div>
        </div>

        <div style="background:var(--paper);border:1px solid var(--border);border-top:3px solid var(--gold);border-radius:4px;padding:1.8rem;box-shadow:0 4px 20px var(--shadow)">
            <h2 style="font-family:var(--font-display);font-size:1.2rem;color:var(--ink);margin-bottom:0.3rem">🔒 Ganti Password</h2>
            <p style="color:var(--ink-light);font-size:0.85rem;margin-bottom:1.4rem;padding-bottom:1rem;border-bottom:1px solid var(--border)">
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

    </div>
</div>

<?php include ('footer.php'); ?>