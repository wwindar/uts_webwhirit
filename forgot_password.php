<?php
session_start();
require_once('db.php');
require_once('auth.php');
require_once('mailer.php');

redirectIfLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Email wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Generate OTP 6 digit
            $otp = sprintf("%06d", mt_rand(0, 999999));
            $expires_at = date("Y-m-d H:i:s", strtotime("+10 minutes"));

            // Simpan ke database
            $stmt_otp = $conn->prepare("INSERT INTO password_resets (user_id, kode, expires_at) VALUES (?, ?, ?)");
            $stmt_otp->bind_param("iss", $user['id'], $otp, $expires_at);
            $stmt_otp->execute();
            $stmt_otp->close();

            // Kirim email
            if (kirimEmailOTP($user['email'], $user['username'], $otp)) {
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_username'] = $user['username'];
                $_SESSION['reset_email'] = $user['email'];
                header("Location: verifikasi_otp.php");
                exit();
            } else {
                $error = 'Gagal mengirim email verifikasi. Silakan coba beberapa saat lagi.';
            }
        } else {
            $error = 'Alamat email tidak terdaftar.';
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

        <p class="auth-subtitle">Masukkan alamat email Anda yang terdaftar untuk menerima kode verifikasi OTP.</p>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="Masukkan email terdaftar" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Kirim Kode OTP</button>
        </form>

        <div class="auth-footer">
            Ingat password Anda? <a href="login.php">Login di sini</a>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>
