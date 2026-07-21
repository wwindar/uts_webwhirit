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

    if (empty($username) || empty($password) || empty($konfirmasi)) {
        $error = 'Username dan Password wajib diisi.';
    } elseif (empty($email) && empty($nomor_telepon)) {
        $error = 'Email atau Nomor Telepon wajib diisi (minimal salah satu).';
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

            $insert = $conn->prepare("INSERT INTO users (username, nama_lengkap, email, nomor_telepon, password) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("sssss", $username, $nama_lengkap, $email, $nomor_telepon, $hashedPassword);

            if ($insert->execute()) {
                // Auto-login
                $_SESSION['user_id'] = $insert->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'user';
                
                require_once('mailer.php');
                if (!empty($email)) {
                    kirimEmailWelcome($email, $username);
                }
                
                $redirect = isset($_SESSION['redirect_to']) ? $_SESSION['redirect_to'] : 'dashboard.php';
                unset($_SESSION['redirect_to']);
                header("Location: " . $redirect);
                exit();
            } else {
                $error = 'Gagal membuat akun. Coba lagi.';
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
                <label for="email">Email <span style="font-size: 0.8em; color: #8C8C94;">(Salah satu dengan No HP wajib diisi)</span></label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="Contoh: user@email.com">
            </div>
            <div class="form-group">
                <label for="nomor_telepon">Nomor Telepon <span style="font-size: 0.8em; color: #8C8C94;">(Salah satu dengan Email wajib diisi)</span></label>
                <input type="text" id="nomor_telepon" name="nomor_telepon"
                       value="<?= htmlspecialchars($_POST['nomor_telepon'] ?? '') ?>"
                       placeholder="Contoh: 08123456789">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Min. 6 karakter" required>
            </div>
            <div class="form-group">
                <label for="konfirmasi">Konfirmasi Password</label>
                <input type="password" id="konfirmasi" name="konfirmasi"
                       placeholder="Ulangi password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Daftar Sekarang</button>
        </form>

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
</script>

</body>
</html>