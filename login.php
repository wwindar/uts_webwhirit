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

        <div class="social-divider">Atau</div>

        <div class="social-btn-container">
            <button type="button" class="btn-social btn-google" onclick="openSocialModal('google')">
                <svg class="social-icon" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v3.9h6.6c-.28 1.48-1.12 2.73-2.38 3.58v3h3.84c2.25-2.07 3.54-5.12 3.54-8.6a8.88 8.88 0 0 0-.16-1.81z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.84-3c-1.07.72-2.44 1.16-4.09 1.16-3.15 0-5.81-2.13-6.76-5.01H1.38v3.13A12 12 0 0 0 12 24z"/><path fill="#FBBC05" d="M5.24 14.24a7.25 7.25 0 0 1 0-2.48V8.63H1.38a12 12 0 0 0 0 6.74l3.86-1.13z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.43-3.43C17.93 1.19 15.2.08 12 .08A12 12 0 0 0 1.38 8.63l3.86 3.13c.95-2.88 3.61-5.01 6.76-5.01z"/></svg>
                Lanjutkan dengan Google
            </button>
        </div>

        <div class="auth-footer">
            Belum punya akun? <a href="register.php">Daftar di sini</a><br><br>
            Lupa password? <a href="forgot_password.php">Reset di sini</a>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

<!-- MODAL SIMULASI GOOGLE -->
<div class="social-modal-overlay" id="modalGoogle">
    <div class="social-modal">
        <button class="social-modal-close" onclick="closeSocialModal('google')">×</button>
        <div class="social-modal-header">
            <svg class="social-modal-logo" viewBox="0 0 24 24" style="width:36px;height:36px;"><path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v3.9h6.6c-.28 1.48-1.12 2.73-2.38 3.58v3h3.84c2.25-2.07 3.54-5.12 3.54-8.6a8.88 8.88 0 0 0-.16-1.81z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.84-3c-1.07.72-2.44 1.16-4.09 1.16-3.15 0-5.81-2.13-6.76-5.01H1.38v3.13A12 12 0 0 0 12 24z"/><path fill="#FBBC05" d="M5.24 14.24a7.25 7.25 0 0 1 0-2.48V8.63H1.38a12 12 0 0 0 0 6.74l3.86-1.13z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.43-3.43C17.93 1.19 15.2.08 12 .08A12 12 0 0 0 1.38 8.63l3.86 3.13c.95-2.88 3.61-5.01 6.76-5.01z"/></svg>
            <h3 class="social-modal-title">Masuk dengan Google</h3>
            <p class="social-modal-subtitle">Gunakan Akun Google Anda untuk masuk</p>
        </div>
        <form action="auth_social.php" method="POST">
            <input type="hidden" name="provider" value="google">
            <div class="form-group">
                <label for="google_email">Email Google</label>
                <input type="email" id="google_email" name="email" placeholder="contoh@gmail.com" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Kirim Kode OTP ke Gmail</button>
        </form>
    </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function (e) {
    const password = document.getElementById('password');
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.textContent = type === 'password' ? '👁️' : '🙈';
});

function openSocialModal(provider) {
    if (provider === 'google') {
        document.getElementById('modalGoogle').classList.add('show');
    }
}

function closeSocialModal(provider) {
    if (provider === 'google') {
        document.getElementById('modalGoogle').classList.remove('show');
    }
}

// Close modal when clicking overlay background
window.addEventListener('click', function(e) {
    const modalGoogle = document.getElementById('modalGoogle');
    if (e.target === modalGoogle) {
        closeSocialModal('google');
    }
});
</script>

</body>
</html>