<?php
session_start();
require_once('db.php');
require_once('auth.php');

redirectIfLoggedIn();

// Pastikan user sudah melewati verifikasi di forgot_password.php
if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_username'])) {
    header("Location: forgot_password.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if (empty($password) || empty($konfirmasi)) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $user_id = $_SESSION['reset_user_id'];

        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $hashedPassword, $user_id);

        if ($update->execute()) {
            $success = 'Password berhasil direset! Silakan login dengan password baru.';
            // Hapus session reset agar tidak bisa diakses lagi
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_username']);
        } else {
            $error = 'Gagal mereset password. Coba lagi.';
        }
        $update->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Katalog Resensi Buku</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <span class="logo-icon">📚</span>
            <h1>Resensi<em>Buku</em></h1>
            <p>Reset Password</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <a href="login.php" class="btn btn-primary btn-full" style="margin-top:15px; text-align:center; text-decoration:none;">Kembali ke Login</a>
        <?php else: ?>

            <p class="auth-subtitle">Buat password baru untuk akun <strong><?= htmlspecialchars($_SESSION['reset_username']) ?></strong>.</p>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="password">Password Baru</label>
                    <input type="password" id="password" name="password"
                           placeholder="Min. 6 karakter" required>
                </div>
                <div class="form-group">
                    <label for="konfirmasi">Konfirmasi Password Baru</label>
                    <input type="password" id="konfirmasi" name="konfirmasi"
                           placeholder="Ulangi password baru" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Simpan Password Baru</button>
            </form>

        <?php endif; ?>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>
