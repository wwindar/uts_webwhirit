<?php
session_start();
require_once('db.php');
require_once('auth.php');

redirectIfLoggedIn();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $kontak = trim($_POST['kontak'] ?? ''); // Email atau Nomor Telepon

    if (empty($username) || empty($kontak)) {
        $error = 'Semua field wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND (email = ? OR nomor_telepon = ?)");
        $stmt->bind_param("sss", $username, $kontak, $kontak);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Set session for password reset
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_username'] = $username;
            header("Location: reset_password.php");
            exit();
        } else {
            $error = 'Kombinasi Username dan Email/No. HP tidak ditemukan atau tidak cocok.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — Katalog Resensi Buku</title>
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
            <p>Lupa Password</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <p class="auth-subtitle">Verifikasi identitas Anda untuk mereset sandi.</p>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="Masukkan username" required>
            </div>
            <div class="form-group">
                <label for="kontak">Email atau Nomor Telepon</label>
                <input type="text" id="kontak" name="kontak"
                       value="<?= htmlspecialchars($_POST['kontak'] ?? '') ?>"
                       placeholder="Email/No HP yang terdaftar" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Verifikasi</button>
        </form>

        <div class="auth-footer">
            Ingat password Anda? <a href="login.php">Login di sini</a>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>
