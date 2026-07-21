<?php
require_once __DIR__ . '/phpmailer_lite/Exception.php';
require_once __DIR__ . '/phpmailer_lite/PHPMailer.php';
require_once __DIR__ . '/phpmailer_lite/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

/**
 * Kirim email notifikasi aktivitas (like, komentar, follow)
 * @param string $emailTujuan  Email penerima
 * @param string $username     Username penerima
 * @param string $tipe         'like' | 'comment' | 'follow'
 * @param string $pesan        Pesan singkat notifikasi (sudah di-format HTML)
 * @param string $linkUrl      URL lengkap ke halaman terkait (opsional)
 */
function kirimEmailNotifikasi($emailTujuan, $username, $tipe, $pesan, $linkUrl = '') {
    try {
        $mail = getMailer();
        $mail->addAddress($emailTujuan);
        $mail->isHTML(true);

        $ikonMap = ['like' => '❤️', 'comment' => '💬', 'follow' => '👤'];
        $ikon = $ikonMap[$tipe] ?? '🔔';

        $judulMap = [
            'like'    => 'Seseorang menyukai resensimu!',
            'comment' => 'Komentar baru di resensimu!',
            'follow'  => 'Kamu punya pengikut baru!',
        ];
        $judul = $judulMap[$tipe] ?? 'Notifikasi baru';

        $mail->Subject = "$ikon $judul — Resensi Buku";

        $tombol = $linkUrl
            ? "<div style='text-align:center;margin:24px 0;'><a href='$linkUrl' style='background:#db2777;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:600;'>Lihat Sekarang →</a></div>"
            : '';

        $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:20px;border:1px solid #eee;border-radius:10px;background:#fff;'>
            <div style='text-align:center;margin-bottom:20px;'>
                <span style='font-size:40px;'>📚</span>
                <h2 style='color:#db2777;margin-top:10px;'>Resensi Buku</h2>
            </div>
            <p>Halo <strong>" . htmlspecialchars($username) . "</strong>,</p>
            <div style='background:#fdf2f8;border-left:4px solid #db2777;padding:14px 18px;border-radius:6px;margin:20px 0;font-size:15px;'>
                $ikon $pesan
            </div>
            $tombol
            <hr style='border:none;border-top:1px solid #eee;margin:24px 0;'>
            <p style='font-size:11px;color:#999;text-align:center;'>Email ini dikirim otomatis oleh sistem Resensi Buku. Kamu dapat mengatur preferensi notifikasi di halaman <a href='https://wwindar.infinityfreeapp.com/uts_webwhirit/pengaturan.php'>Pengaturan</a>.</p>
        </div>
        ";

        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log("Gagal kirim email notifikasi: " . $e->getMessage());
        return false;
    }
}

function kirimEmailWelcome($emailTujuan, $username) {
    try {
        $mail = getMailer();
        $mail->addAddress($emailTujuan);
        $mail->isHTML(true);
        $mail->Subject = '🎉 Selamat Datang di Resensi Buku!';
        $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:20px;border:1px solid #eee;border-radius:10px;background:#fff;'>
            <div style='text-align:center;margin-bottom:20px;'>
                <span style='font-size:40px;'>📚</span>
                <h2 style='color:#db2777;margin-top:10px;'>Resensi Buku</h2>
            </div>
            <p>Halo <strong>" . htmlspecialchars($username) . "</strong>,</p>
            <p>Selamat bergabung! Akun kamu berhasil dibuat.</p>
            <p>Mulai bagikan ulasan buku favoritmu dan temukan inspirasi bacaan baru dari komunitas kami.</p>
            <div style='text-align:center;margin:30px 0;'>
                <a href='https://wwindar.infinityfreeapp.com/uts_webwhirit/login.php' style='background:#db2777;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;'>Mulai Sekarang</a>
            </div>
            <hr style='border:none;border-top:1px solid #eee;margin:30px 0;'>
            <p style='font-size:11px;color:#999;text-align:center;'>Email ini dikirim otomatis oleh sistem Resensi Buku.</p>
        </div>
        ";
        $mail->send();
        return true;
    } catch (\Exception $e) {
        return false;
    }
}

function kirimEmailLogin($emailTujuan, $username) {
    try {
        $mail = getMailer();
        $mail->addAddress($emailTujuan);
        $mail->isHTML(true);
        $mail->Subject = 'Pemberitahuan Login — Resensi Buku';
        date_default_timezone_set('Asia/Jakarta');
        $waktu = date('d M Y H:i:s');
        $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:20px;border:1px solid #eee;border-radius:10px;background:#fff;'>
            <div style='text-align:center;margin-bottom:20px;'>
                <span style='font-size:40px;'>📚</span>
                <h2 style='color:#db2777;margin-top:10px;'>Resensi Buku</h2>
            </div>
            <p>Halo <strong>" . htmlspecialchars($username) . "</strong>,</p>
            <p>Kami mendeteksi login ke akun kamu pada <strong>" . $waktu . " WIB</strong>.</p>
            <p>Jika ini adalah kamu, abaikan saja email ini. Jika bukan, segera amankan akun kamu dengan mengganti password.</p>
            <hr style='border:none;border-top:1px solid #eee;margin:30px 0;'>
            <p style='font-size:11px;color:#999;text-align:center;'>Email ini dikirim otomatis oleh sistem Resensi Buku.</p>
        </div>
        ";
        $mail->send();
        return true;
    } catch (\Exception $e) {
        return false;
    }
}

function kirimEmailOTPRegister($emailTujuan, $username, $kodeOTP) {
    try {
        $mail = getMailer();
        $mail->addAddress($emailTujuan);
        $mail->isHTML(true);
        $mail->Subject = 'Kode Verifikasi Pendaftaran — Resensi Buku';
        $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:20px;border:1px solid #eee;border-radius:10px;background:#fff;'>
            <div style='text-align:center;margin-bottom:20px;'>
                <span style='font-size:40px;'>📚</span>
                <h2 style='color:#db2777;margin-top:10px;'>Resensi Buku</h2>
            </div>
            <p>Halo <strong>" . htmlspecialchars($username) . "</strong>,</p>
            <p>Terima kasih telah mendaftar di Resensi Buku! Untuk menyelesaikan proses pendaftaran dan mengaktifkan akun Anda, silakan masukkan kode verifikasi OTP berikut:</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <div style='display: inline-block; font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #db2777; background: #fdf2f8; padding: 15px 30px; border-radius: 8px; border: 2px dashed #f472b6;'>
                    $kodeOTP
                </div>
            </div>

            <p style='color: #ef4444; font-weight: 500;'>Kode ini akan kedaluwarsa dalam waktu 15 menit.</p>
            <p>Jika Anda tidak merasa mendaftar di situs kami, Anda bisa mengabaikan email ini.</p>
            <hr style='border:none;border-top:1px solid #eee;margin:30px 0;'>
            <p style='font-size:11px;color:#999;text-align:center;'>Email ini dikirim otomatis oleh sistem Resensi Buku.</p>
        </div>
        ";
        $mail->send();
        return true;
    } catch (\Exception $e) {
        return false;
    }
}
?>
