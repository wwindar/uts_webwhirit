<?php
require_once __DIR__ . '/phpmailer_lite/Exception.php';
require_once __DIR__ . '/phpmailer_lite/PHPMailer.php';
require_once __DIR__ . '/phpmailer_lite/SMTP.php';

function getMailer() {
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'newwinda9@gmail.com';
    $mail->Password   = 'myvipqqxunqxqkxy'; // App Password dari Google
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // Recipients
    $mail->setFrom('newwinda9@gmail.com', 'Resensi Buku Support');
    return $mail;
}

function kirimEmailOTP($emailTujuan, $username, $kodeOTP) {
    try {
        $mail = getMailer();
        $mail->addAddress($emailTujuan);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Kode Verifikasi OTP Reset Password - Resensi Buku';
        
        $mail->Body    = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; background: #fff;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <span style='font-size: 40px;'>📚</span>
                <h2 style='color: #db2777; margin-top: 10px;'>Resensi Buku</h2>
            </div>
            <p>Halo <strong>" . htmlspecialchars($username) . "</strong>,</p>
            <p>Kami menerima permintaan untuk menyetel ulang kata sandi akun Anda. Gunakan kode verifikasi OTP di bawah ini:</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <div style='display: inline-block; font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #db2777; background: #fdf2f8; padding: 15px 30px; border-radius: 8px; border: 2px dashed #f472b6;'>
                    $kodeOTP
                </div>
            </div>

            <p style='color: #ef4444; font-weight: 500;'>Kode ini hanya berlaku selama 10 menit.</p>
            <p>Jika Anda tidak meminta pengaturan ulang ini, abaikan saja email ini dan pastikan akun Anda tetap aman.</p>
            <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
            <p style='font-size: 11px; color: #999; text-align: center;'>Email ini dikirim secara otomatis oleh sistem Resensi Buku.</p>
        </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Gagal mengirim email OTP: {$mail->ErrorInfo}");
        return false;
    }
}
?>
