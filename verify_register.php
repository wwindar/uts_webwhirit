<?php
session_start();
require_once('db.php');
require_once('auth.php');

if (!isset($_SESSION['verify_user_id']) || !isset($_SESSION['verify_email'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$user_id = intval($_SESSION['verify_user_id']);
$email = $_SESSION['verify_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend'])) {
        // Resend OTP logic
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));
        
        $stmt_otp = $conn->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE id = ?");
        $stmt_otp->bind_param("ssi", $otp, $expires, $user_id);
        if ($stmt_otp->execute()) {
            // Fetch username
            $res = $conn->query("SELECT username FROM users WHERE id = $user_id");
            $username = $res->fetch_assoc()['username'] ?? 'User';
            
            require_once('mailer.php');
            kirimEmailOTPRegister($email, $username, $otp);
            $success = 'Kode OTP baru telah dikirim ke email Anda.';
        } else {
            $error = 'Gagal mengirim ulang kode OTP.';
        }
        $stmt_otp->close();
    } else {
        // Verify OTP logic
        $otp = trim($_POST['otp'] ?? '');
        if (empty($otp)) {
            $error = 'Kode OTP wajib diisi.';
        } else {
            $now = date("Y-m-d H:i:s");
            
            $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id = ? AND otp_code = ? AND otp_expires > ? LIMIT 1");
            $stmt->bind_param("iss", $user_id, $otp, $now);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res->num_rows === 1) {
                $user = $res->fetch_assoc();
                
                // Update to verified and clear OTP
                $update = $conn->query("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires = NULL WHERE id = $user_id");
                
                // Auto login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Send welcome email
                require_once('mailer.php');
                kirimEmailWelcome($email, $user['username']);
                
                // Cleanup session
                unset($_SESSION['verify_user_id']);
                unset($_SESSION['verify_email']);
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Kode OTP salah atau sudah kedaluwarsa.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Akun — Katalog Resensi Buku</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <style>
        .otp-input-container {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <span class="logo-icon">📧</span>
            <h1>Resensi<em>Buku</em></h1>
            <p>Verifikasi Pendaftaran</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <p class="auth-subtitle">Kami telah mengirimkan kode OTP 6-digit ke email <strong><?= htmlspecialchars($email) ?></strong>. Silakan masukkan di bawah ini untuk mengaktifkan akun Anda.</p>

        <form method="POST" action="">
            <div class="form-group" style="text-align: center;">
                <label>Kode OTP</label>
                <div class="otp-input-container">
                    <input type="text" name="otp" class="form-control" style="text-align: center; font-size: 1.5rem; letter-spacing: 8px; font-weight: bold;" maxlength="6" placeholder="------" required autocomplete="off">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Verifikasi & Mulai</button>
        </form>

        <div class="auth-footer" style="margin-top: 15px;">
            Belum menerima kode? 
            <form method="POST" action="" style="display:inline;">
                <input type="hidden" name="resend" value="1">
                <button type="submit" style="background:none; border:none; color:var(--gold); font-weight:bold; cursor:pointer; padding:0; text-decoration:underline;">Kirim Ulang</button>
            </form>
        </div>
        
        <div class="auth-footer" style="margin-top: 5px;">
            Atau <a href="login.php">Kembali ke Login</a>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>
