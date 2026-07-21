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

        $stmt = $conn->prepare("SELECT id, username, email, password, role, is_verified FROM users WHERE username = ? OR (email != '' AND email = ?) OR (nomor_telepon != '' AND nomor_telepon = ?)");
        $stmt->bind_param("sss", $username, $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                if (isset($user['is_verified']) && $user['is_verified'] == 0) {
                    $_SESSION['verify_user_id'] = $user['id'];
                    $_SESSION['verify_email'] = $user['email'];
                    
                    // Optionally generate and send a new OTP here
                    $otp = sprintf("%06d", mt_rand(100000, 999999));
                    $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));
                    $conn->query("UPDATE users SET otp_code = '$otp', otp_expires = '$expires' WHERE id = " . $user['id']);
                    require_once('mailer.php');
                    kirimEmailOTPRegister($user['email'], $user['username'], $otp);
                    
                    header("Location: verify_register.php");
                    exit();
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                require_once('mailer.php');
                if (!empty($user['email'])) {
                    kirimEmailLogin($user['email'], $user['username']);
                }
                
                $redirect = isset($_SESSION['redirect_to']) ? $_SESSION['redirect_to'] : 'index.php';
                unset($_SESSION['redirect_to']);
                header("Location: " . $redirect);
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
            <div class="form-group" style="position: relative;">
                <label for="password">Password</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="password" name="password"
                           placeholder="Masukkan password" required autocomplete="current-password"
                           style="width: 100%; padding-right: 40px;">
                    <span id="togglePassword" style="position: absolute; right: 10px; cursor: pointer; color: var(--ink-light); font-size: 1.2rem; user-select: none;">👁️</span>
                </div>
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

<script>
document.getElementById('togglePassword').addEventListener('click', function (e) {
    const password = document.getElementById('password');
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.textContent = type === 'password' ? '👁️' : '🙈';
});
</script>

</body>
</html>