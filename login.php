<?php

session_start();
require_once ('db.php');
require_once ('auth.php');

redirectIfLoggedIn();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username/Email/No HP dan password wajib diisi.';
    } else {

        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? OR (email != '' AND email = ?) OR (nomor_telepon != '' AND nomor_telepon = ?)");
        $stmt->bind_param("sss", $username, $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Password yang anda masukkan salah.';
            }
        } else {
            $error = 'Akun tidak ditemukan.';
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
    <title>Login — Katalog Resensi Buku</title>
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

        <p class="auth-subtitle">Masuk ke Akun Anda</p>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username, Email, atau No. HP</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="Masukkan username, email, atau no. hp" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Masukkan password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-full">Masuk</button>
        </form>

        <div class="auth-footer">
            Belum punya akun? <a href="register.php">Daftar di sini</a><br><br>
            Lupa password? <a href="forgot_password.php">Reset di sini</a>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

</body>
</html>