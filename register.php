<?php

session_start();
require_once('db.php');
require_once ('auth.php');

redirectIfLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nomor_telepon = trim($_POST['nomor_telepon'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($konfirmasi)) {
        $error = 'Username, Email, dan Password wajib diisi.';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter.';
    } elseif (!preg_match('/^[a-z0-9_.]+$/', $username)) {
        $error = 'Username hanya boleh berisi huruf kecil, angka, titik (.), dan garis bawah (_).';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR (email != '' AND email = ?) OR (nomor_telepon != '' AND nomor_telepon = ?)");
        $stmt->bind_param("sss", $username, $email, $nomor_telepon);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Username, Email, atau Nomor Telepon sudah digunakan.';
            $stmt->close();
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->close();

            $email_val = empty($email) ? null : $email;
            $nomor_telepon_val = empty($nomor_telepon) ? null : $nomor_telepon;

            // Set is_verified = 0 explicitly in query although default is 0
            $insert = $conn->prepare("INSERT INTO users (username, nama_lengkap, email, nomor_telepon, password, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
            $insert->bind_param("sssss", $username, $nama_lengkap, $email_val, $nomor_telepon_val, $hashedPassword);

            if ($insert->execute()) {
                $newUserId = $insert->insert_id;
                
                // Generate OTP
                $otp = sprintf("%06d", mt_rand(100000, 999999));
                $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));
                
                $stmt_otp = $conn->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE id = ?");
                $stmt_otp->bind_param("ssi", $otp, $expires, $newUserId);
                $stmt_otp->execute();
                $stmt_otp->close();
                
                require_once('mailer.php');
                kirimEmailOTPRegister($email, $username, $otp);
                
                // Set session for verification
                $_SESSION['verify_user_id'] = $newUserId;
                $_SESSION['verify_email'] = $email;
                
                header("Location: verify_register.php");
                exit();
            } else {
                $error = 'Gagal membuat akun. Coba lagi. (Error: ' . $insert->error . ')';
            }
            $insert->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — Katalog Resensi Buku</title>
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
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <p class="auth-subtitle">Buat Akun Baru</p>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username <span style="color:#c0392b">*</span></label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="a-z, 0-9, titik, garis bawah" 
                       pattern="[a-z0-9_.]+" 
                       title="Hanya boleh huruf kecil, angka, titik (.), dan garis bawah (_)" 
                       required>
            </div>
            <div class="form-group">
                <label for="nama_lengkap">Nama Tampilan (Opsional)</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap"
                       value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>"
                       placeholder="Nama asli atau nama pena Anda">
            </div>
            <div class="form-group">
                <label for="email">Email <span style="font-size: 0.8em; color: #8C8C94;">(Wajib)</span></label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="Contoh: user@email.com" required>
            </div>
            <div class="form-group">
                <label for="nomor_telepon">Nomor Telepon <span style="font-size: 0.8em; color: #8C8C94;">(Opsional)</span></label>
                <input type="text" id="nomor_telepon" name="nomor_telepon"
                       value="<?= htmlspecialchars($_POST['nomor_telepon'] ?? '') ?>"
                       placeholder="Contoh: 08123456789">
            </div>
            <div class="form-group" style="position: relative;">
                <label for="password">Password</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="password" name="password"
                           placeholder="Min. 6 karakter" required
                           style="width: 100%; padding-right: 40px;">
                    <span id="togglePasswordReg" style="position: absolute; right: 10px; cursor: pointer; color: var(--ink-light); font-size: 1.2rem; user-select: none;">👁️</span>
                </div>
            </div>
            <div class="form-group" style="position: relative;">
                <label for="konfirmasi">Konfirmasi Password</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="konfirmasi" name="konfirmasi"
                           placeholder="Ulangi password" required
                           style="width: 100%; padding-right: 40px;">
                    <span id="toggleKonfirmasiReg" style="position: absolute; right: 10px; cursor: pointer; color: var(--ink-light); font-size: 1.2rem; user-select: none;">👁️</span>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Daftar Sekarang</button>
        </form>

        <div class="social-divider">Atau</div>

        <div class="social-btn-container">
            <button type="button" class="btn-social btn-google" onclick="openSocialModal('google')">
                <svg class="social-icon" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v3.9h6.6c-.28 1.48-1.12 2.73-2.38 3.58v3h3.84c2.25-2.07 3.54-5.12 3.54-8.6a8.88 8.88 0 0 0-.16-1.81z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.84-3c-1.07.72-2.44 1.16-4.09 1.16-3.15 0-5.81-2.13-6.76-5.01H1.38v3.13A12 12 0 0 0 12 24z"/><path fill="#FBBC05" d="M5.24 14.24a7.25 7.25 0 0 1 0-2.48V8.63H1.38a12 12 0 0 0 0 6.74l3.86-1.13z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.43-3.43C17.93 1.19 15.2.08 12 .08A12 12 0 0 0 1.38 8.63l3.86 3.13c.95-2.88 3.61-5.01 6.76-5.01z"/></svg>
                Lanjutkan dengan Google
            </button>
        </div>

        <div class="auth-footer">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

<script>
const usernameInput = document.getElementById('username');
const formGroup = usernameInput.closest('.form-group');

// Buat container feedback
const feedbackDiv = document.createElement('div');
feedbackDiv.id = 'reg-username-feedback';
feedbackDiv.style.cssText = 'margin-top:0.4rem;font-size:0.82rem;display:none;border-radius:8px;padding:0.5rem 0.75rem;';
formGroup.appendChild(feedbackDiv);

const suggestionsDiv = document.createElement('div');
suggestionsDiv.id = 'reg-username-suggestions';
suggestionsDiv.style.cssText = 'margin-top:0.4rem;display:none;';
formGroup.appendChild(suggestionsDiv);

let timer = null;

usernameInput.addEventListener('input', function() {
    this.value = this.value.toLowerCase().replace(/\s/g, '');
    
    clearTimeout(timer);
    const val = this.value;
    
    feedbackDiv.style.display = 'none';
    suggestionsDiv.style.display = 'none';
    suggestionsDiv.innerHTML = '';
    
    if (!val) return;
    
    if (!/^[a-z0-9_.]+$/.test(val)) {
        feedbackDiv.style.display = 'block';
        feedbackDiv.innerHTML = '⚠️ <span style="color:#e74c3c;font-weight:600">Karakter tidak diperbolehkan!</span><br>' +
            '<span style="color:#888;font-size:0.78rem">Hanya boleh: huruf kecil (a-z), angka (0-9), titik (.) dan garis bawah (_)</span>';
        feedbackDiv.style.background = '#fef2f2';
        feedbackDiv.style.border = '1px solid #fecaca';
        return;
    }
    
    if (val.length < 4) {
        feedbackDiv.style.display = 'block';
        feedbackDiv.innerHTML = '⚠️ <span style="color:#f59e0b">Username minimal 4 karakter.</span>';
        feedbackDiv.style.background = '#fffbeb';
        feedbackDiv.style.border = '1px solid #fde68a';
        return;
    }
    
    feedbackDiv.style.display = 'block';
    feedbackDiv.innerHTML = '⏳ <span style="color:#888">Memeriksa ketersediaan...</span>';
    feedbackDiv.style.background = '#f8f9fa';
    feedbackDiv.style.border = '1px solid #e5e7eb';
    
    timer = setTimeout(() => {
        fetch('cek_username_publik.php?username=' + encodeURIComponent(val))
        .then(r => r.json())
        .then(data => {
            feedbackDiv.style.display = 'block';
            
            if (data.status === 'available') {
                feedbackDiv.innerHTML = '✅ <span style="color:#10b981;font-weight:600">Username tersedia!</span>';
                feedbackDiv.style.background = '#f0fdf4';
                feedbackDiv.style.border = '1px solid #bbf7d0';
                suggestionsDiv.style.display = 'none';
            } else if (data.status === 'taken') {
                feedbackDiv.innerHTML = '❌ <span style="color:#e74c3c;font-weight:600">Username sudah dipakai.</span>';
                feedbackDiv.style.background = '#fef2f2';
                feedbackDiv.style.border = '1px solid #fecaca';
                
                if (data.suggestions && data.suggestions.length > 0) {
                    suggestionsDiv.style.display = 'block';
                    suggestionsDiv.style.cssText += 'background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:0.6rem 0.75rem;';
                    
                    let html = '<div style="font-size:0.78rem;color:#0369a1;font-weight:600;margin-bottom:0.4rem">💡 Rekomendasi username:</div>';
                    html += '<div style="display:flex;flex-wrap:wrap;gap:0.35rem">';
                    data.suggestions.forEach(s => {
                        html += `<button type="button" onclick="document.getElementById('username').value='${s}';document.getElementById('username').dispatchEvent(new Event('input'))" 
                            style="background:#e0f2fe;border:1px solid #7dd3fc;border-radius:16px;
                            padding:0.25rem 0.7rem;font-size:0.8rem;cursor:pointer;color:#0c4a6e;
                            font-weight:500;transition:all 0.15s"
                            onmouseover="this.style.background='#bae6fd'"
                            onmouseout="this.style.background='#e0f2fe'">${s}</button>`;
                    });
                    html += '</div>';
                    suggestionsDiv.innerHTML = html;
                }
            } else if (data.status === 'invalid') {
                feedbackDiv.innerHTML = '⚠️ <span style="color:#e74c3c">' + data.message + '</span>';
                feedbackDiv.style.background = '#fef2f2';
                feedbackDiv.style.border = '1px solid #fecaca';
            }
        })
        .catch(() => {
            feedbackDiv.style.display = 'none';
        });
    }, 500);
});

// Script for toggling password visibility
document.getElementById('togglePasswordReg').addEventListener('click', function () {
    const password = document.getElementById('password');
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.textContent = type === 'password' ? '👁️' : '🙈';
});

document.getElementById('toggleKonfirmasiReg').addEventListener('click', function () {
    const konfirmasi = document.getElementById('konfirmasi');
    const type = konfirmasi.getAttribute('type') === 'password' ? 'text' : 'password';
    konfirmasi.setAttribute('type', type);
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

<!-- MODAL SIMULASI GOOGLE -->
<div class="social-modal-overlay" id="modalGoogle">
    <div class="social-modal">
        <button class="social-modal-close" onclick="closeSocialModal('google')">×</button>
        <div class="social-modal-header">
            <svg class="social-modal-logo" viewBox="0 0 24 24" style="width:36px;height:36px;"><path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v3.9h6.6c-.28 1.48-1.12 2.73-2.38 3.58v3h3.84c2.25-2.07 3.54-5.12 3.54-8.6a8.88 8.88 0 0 0-.16-1.81z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.84-3c-1.07.72-2.44 1.16-4.09 1.16-3.15 0-5.81-2.13-6.76-5.01H1.38v3.13A12 12 0 0 0 12 24z"/><path fill="#FBBC05" d="M5.24 14.24a7.25 7.25 0 0 1 0-2.48V8.63H1.38a12 12 0 0 0 0 6.74l3.86-1.13z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.43-3.43C17.93 1.19 15.2.08 12 .08A12 12 0 0 0 1.38 8.63l3.86 3.13c.95-2.88 3.61-5.01 6.76-5.01z"/></svg>
            <h3 class="social-modal-title">Daftar dengan Google</h3>
            <p class="social-modal-subtitle">Gunakan Akun Google Anda untuk mendaftar</p>
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

</body>
</html>