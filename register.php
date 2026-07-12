<?php

session_start();
require_once('db.php');
require_once ('auth.php');

redirectIfLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nomor_telepon = trim($_POST['nomor_telepon'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if (empty($username) || empty($password) || empty($konfirmasi)) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR (email != '' AND email = ?) OR (nomor_telepon != '' AND nomor_telepon = ?)");
        $stmt->bind_param("sss", $username, $email, $nomor_telepon);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Username, Email, atau Nomor Telepon sudah digunakan.';
            $stmt->close();
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->close();

            $insert = $conn->prepare("INSERT INTO users (username, nama_lengkap, email, nomor_telepon, password) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("sssss", $username, $nama_lengkap, $email, $nomor_telepon, $hashedPassword);

            if ($insert->execute()) {
                // Auto-login
                $_SESSION['user_id'] = $insert->insert_id;
                $_SESSION['username'] = $username;
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Gagal membuat akun. Coba lagi.';
            }
            $insert->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — Katalog Resensi Buku</title>
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
            <p>Katalog Ulasan Buku</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <p class="auth-subtitle">Buat Akun Baru</p>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username <span style="color:#c0392b">*</span></label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="Min. 4 karakter (Tanpa spasi)" required>
            </div>
            <div class="form-group">
                <label for="nama_lengkap">Nama Tampilan (Opsional)</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap"
                       value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>"
                       placeholder="Nama asli atau nama pena Anda">
            </div>
            <div class="form-group">
                <label for="email">Email (Opsional)</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="Contoh: user@email.com">
            </div>
            <div class="form-group">
                <label for="nomor_telepon">Nomor Telepon (Opsional)</label>
                <input type="text" id="nomor_telepon" name="nomor_telepon"
                       value="<?= htmlspecialchars($_POST['nomor_telepon'] ?? '') ?>"
                       placeholder="Contoh: 08123456789">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Min. 6 karakter" required>
            </div>
            <div class="form-group">
                <label for="konfirmasi">Konfirmasi Password</label>
                <input type="password" id="konfirmasi" name="konfirmasi"
                       placeholder="Ulangi password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Daftar Sekarang</button>
        </form>

        <div class="auth-footer">
            Sudah punya akun? <a href="index.php">Login di sini</a>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

</body>
</html>