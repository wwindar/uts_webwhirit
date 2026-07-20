<?php
session_start();
require_once('db.php');
require_once('auth.php');

redirectIfLoggedIn();

if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');

    if (empty($otp)) {
        $error = 'Kode OTP wajib diisi.';
    } else {
        $user_id = $_SESSION['reset_user_id'];
        $now = date("Y-m-d H:i:s");

        // Periksa kode OTP di database
        $stmt = $conn->prepare("SELECT id FROM password_resets WHERE user_id = ? AND kode = ? AND expires_at > ? AND used = 0 ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("iss", $user_id, $otp, $now);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $reset_record = $res->fetch_assoc();
            
            // Set session bahwa OTP telah sukses diverifikasi
            $_SESSION['otp_verified'] = true;
            $_SESSION['otp_record_id'] = $reset_record['id'];
            
            header("Location: reset_password.php");
            exit();
        } else {
            $error = 'Kode OTP salah, tidak cocok, atau sudah kedaluwarsa.';
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
    <title>Verifikasi OTP — Katalog Resensi Buku</title>
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
        .otp-box {
            width: 45px;
            height: 45px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            border: 2px solid var(--border);
            border-radius: 8px;
            background: var(--paper);
            color: var(--ink);
        }
        .otp-box:focus {
            border-color: var(--rose);
            outline: none;
            box-shadow: 0 0 5px rgba(219, 39, 119, 0.3);
        }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <span class="logo-icon">🔑</span>
            <h1>Resensi<em>Buku</em></h1>
            <p>Verifikasi Email</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <p class="auth-subtitle">Kami telah mengirimkan kode OTP 6-digit ke email <strong><?= htmlspecialchars($_SESSION['reset_email']) ?></strong>. Silakan masukkan di bawah ini.</p>

        <form method="POST" action="">
            <div class="form-group" style="text-align: center;">
                <label>Kode OTP</label>
                <div class="otp-input-container">
                    <input type="text" name="otp" class="form-control" style="text-align: center; font-size: 1.5rem; letter-spacing: 8px; font-weight: bold;" maxlength="6" placeholder="------" required autocomplete="off">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Verifikasi Kode</button>
        </form>

        <div class="auth-footer">
            Kembali ke <a href="forgot_password.php">Lupa Password</a>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>
