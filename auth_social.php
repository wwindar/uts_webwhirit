<?php
session_start();
require_once('db.php');
require_once('mailer.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$provider = $_POST['provider'] ?? '';

if ($provider === 'google') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: login.php?error=email_invalid");
        exit();
    }

    // Cek apakah user sudah ada
    $stmt = $conn->prepare("SELECT id, username, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $userId = $user['id'];
        $username = $user['username'];
    } else {
        // Buat user baru
        $emailParts = explode('@', $email);
        $baseUsername = preg_replace('/[^a-zA-Z0-9_.]/', '', $emailParts[0]);
        if (empty($baseUsername)) {
            $baseUsername = "user_" . mt_rand(1000, 9999);
        }
        
        // Cek keunikan username
        $username = $baseUsername;
        $counter = 1;
        while (true) {
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                $check->close();
                break;
            }
            $username = $baseUsername . $counter;
            $counter++;
            $check->close();
        }
        
        $fullName = ucwords(str_replace(['.', '_'], ' ', $baseUsername));
        $randomPass = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
        
        $insert = $conn->prepare("INSERT INTO users (username, nama_lengkap, email, nomor_telepon, password, is_verified, role) VALUES (?, ?, ?, NULL, ?, 0, 'user')");
        $insert->bind_param("ssss", $username, $fullName, $email, $randomPass);
        $insert->execute();
        $userId = $insert->insert_id;
        $insert->close();
    }
    $stmt->close();

    // Generate OTP
    $otp = sprintf("%06d", mt_rand(100000, 999999));
    $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));
    
    $conn->query("UPDATE users SET otp_code = '$otp', otp_expires = '$expires', is_verified = 0 WHERE id = $userId");
    
    // Kirim OTP Asli ke Email
    if (kirimEmailOTPRegister($email, $username, $otp)) {
        $_SESSION['verify_user_id'] = $userId;
        $_SESSION['verify_email'] = $email;
        header("Location: verify_register.php");
        exit();
    } else {
        header("Location: login.php?error=send_failed");
        exit();
    }

} else {
    header("Location: login.php");
    exit();
}
?>
