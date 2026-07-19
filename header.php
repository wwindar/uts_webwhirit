<?php
// Hitung notifikasi belum dibaca untuk badge di navbar
$notifCount = 0;
if (isset($_SESSION['user_id'])) {
    global $conn;
    if (isset($conn)) {
        $uid = $_SESSION['user_id'];
        $nr  = $conn->query("SELECT COUNT(*) as c FROM notifikasi WHERE user_id = $uid AND sudah_dibaca = 0");
        if ($nr) $notifCount = $nr->fetch_assoc()['c'];
    }
}

// Hitung DM belum dibaca
$unreadDmCount = 0;
if (isset($_SESSION['user_id']) && isset($conn)) {
    $uid = $_SESSION['user_id'];
    $ndm  = $conn->query("SELECT COUNT(*) as c FROM pesan WHERE penerima_id = $uid AND dibaca = 0");
    if ($ndm) $unreadDmCount = $ndm->fetch_assoc()['c'];
}

// Ambil info user untuk dropdown profil
$navUserFoto = 'default.png';
$navUsername = 'User';
$navNamaLengkap = '';
if (isset($_SESSION['user_id']) && isset($conn)) {
    $uid = $_SESSION['user_id'];
    $u_res = $conn->query("SELECT username, nama_lengkap, foto_profil FROM users WHERE id = $uid");
    if ($u_res && $u_res->num_rows > 0) {
        $u_data = $u_res->fetch_assoc();
        $navUsername = $u_data['username'];
        $navNamaLengkap = $u_data['nama_lengkap'] ?? '';
        if (!empty($u_data['foto_profil'])) $navUserFoto = $u_data['foto_profil'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // pageFullTitle: diisi oleh halaman untuk override full title (misal nama user di profil)
    // pageTitle: judul halaman biasa
    if (isset($pageFullTitle)) {
        echo '<title>' . htmlspecialchars($pageFullTitle) . '</title>';
    } else {
        $siteName = 'ResensiBуkу'; // pakai karakter mirip biar unik
        $siteName = 'Resensi Buku';
        echo '<title>' . (isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' . $siteName : $siteName) . '</title>';
    }
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📚</text></svg>">
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</head>
<body>
<nav class="navbar">
    <a href="dashboard.php" class="nav-brand">
        <span class="brand-icon">📚</span>
        <span class="brand-name">Resensi<em>Buku</em></span>
    </a>
    <ul class="nav-links" id="navLinks">
        <li>
            <button id="themeToggleBtn" onclick="toggleTheme()" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--ink);" title="Ubah Tema">🌙</button>
        </li>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <li class="nav-dropdown-container">
            <div class="nav-profile-toggle" onclick="toggleNavDropdown()">
                <div class="nav-profile-img-wrapper">
                    <img src="uploads/<?= htmlspecialchars($navUserFoto) ?>" alt="Profile">
                    <?php $totalBadge = $notifCount + $unreadDmCount; if ($totalBadge > 0): ?>
                        <span class="nav-badge-dot"></span>
                    <?php endif; ?>
                </div>
                <span class="nav-dropdown-arrow">▼</span>
            </div>
            
            <div class="nav-dropdown-menu" id="navDropdownMenu">
                <a href="profil.php" class="dropdown-item">👤 Profil saya</a>
                <a href="index.php" class="dropdown-item">🏠 Beranda</a>
                <hr class="nav-dropdown-divider">
                <a href="dm.php" class="dropdown-item">
                    💬 Kotak masuk
                    <?php if($unreadDmCount > 0) echo "<span class='dropdown-badge'>$unreadDmCount</span>"; ?>
                </a>
                <a href="notifikasi.php" class="dropdown-item">
                    🔔 Pembaruan
                    <?php if($notifCount > 0) echo "<span class='dropdown-badge'>$notifCount</span>"; ?>
                </a>
                <a href="katalog.php" class="dropdown-item">📚 Katalog Resensi</a>
                <hr class="nav-dropdown-divider">
                <a href="wishlist.php" class="dropdown-item">📌 Wishlist</a>
                <a href="bookmarks.php" class="dropdown-item">🔖 Bookmark</a>
                <a href="pengguna.php" class="dropdown-item">👥 Pengguna</a>
                <a href="tambah.php" class="dropdown-item">➕ Tambah Resensi</a>
                <hr class="nav-dropdown-divider">
                <?php if (isAdmin()): ?>
                    <a href="admin_users.php" class="dropdown-item" style="color:var(--gold); font-weight:600;">🛠️ Kelola Pengguna</a>
                    <hr class="nav-dropdown-divider">
                <?php endif; ?>
                <a href="about.php" class="dropdown-item">📖 Tentang Aplikasi</a>
                <a href="bantuan.php" class="dropdown-item">❓ Bantuan</a>
                <a href="pengaturan.php" class="dropdown-item">⚙️ Pengaturan</a>
                <a href="logout.php" class="dropdown-item text-danger">🚪 Keluar</a>
            </div>
        </li>
        <?php else: ?>
        <li><a href="katalog.php">Katalog</a></li>
        <li><a href="login.php">Masuk</a></li>
        <li><a href="register.php" class="nav-register-btn" style="background:var(--rose); color:white; padding: 0.5rem 1rem; border-radius: 20px;">Daftar</a></li>
        <?php endif; ?>
    </ul>
</nav>

<script>
function toggleNavDropdown() {
    var menu = document.getElementById("navDropdownMenu");
    menu.classList.toggle("show");
}

// Close dropdown if clicked outside
window.onclick = function(event) {
    if (!event.target.closest('.nav-dropdown-container')) {
        var dropdowns = document.getElementsByClassName("nav-dropdown-menu");
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
}

// Update the icon based on current theme on load
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('themeToggleBtn');
    if (btn) {
        btn.textContent = document.documentElement.getAttribute('data-theme') === 'dark' ? '☀️' : '🌙';
    }
});

function toggleTheme() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const newTheme = isDark ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    const btn = document.getElementById('themeToggleBtn');
    if (btn) btn.textContent = newTheme === 'dark' ? '☀️' : '🌙';
}
</script>

<?php if (isset($_SESSION['user_id'])): 
    // Get max notification ID for initial polling cursor
    $notifUserId = intval($_SESSION['user_id']);
    $maxIdRes = $conn->query("SELECT MAX(id) as m FROM notifikasi WHERE user_id = $notifUserId");
    $maxNotifId = $maxIdRes ? intval($maxIdRes->fetch_assoc()['m']) : 0;
    // Determine base URL for API calls
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    // If we're in a subdirectory of htdocs, go up to root
    $apiBase = $baseUrl . (str_contains($baseDir, 'uts_webwhirit') ? '/uts_webwhirit' : $baseDir);
?>
<div id="toast-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;"></div>

<script>
let lastNotifId = <?= $maxNotifId ?>;

setInterval(() => {
    const apiUrl = '<?= rtrim($apiBase, "/") ?>/api_notif.php?last_id=' + lastNotifId;
    fetch(apiUrl)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' && data.notifs.length > 0) {
            data.notifs.forEach(notif => {
                lastNotifId = Math.max(lastNotifId, notif.id);
                showToast(notif.pesan);
            });
            // Update red dot indicator
            let badge = document.querySelector('.nav-badge-dot');
            if (!badge) {
                let wrap = document.querySelector('.nav-profile-img-wrapper');
                if (wrap) {
                    badge = document.createElement('span');
                    badge.className = 'nav-badge-dot';
                    wrap.appendChild(badge);
                }
            }
        }
    })
    .catch(err => console.error(err));
}, 5000); // Poll setiap 5 detik

function showToast(message) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.style.background = 'var(--paper)';
    toast.style.color = 'var(--ink)';
    toast.style.padding = '12px 20px';
    toast.style.borderRadius = '8px';
    toast.style.boxShadow = '0 4px 15px var(--shadow)';
    toast.style.borderLeft = '4px solid var(--gold)';
    toast.style.cursor = 'pointer';
    toast.style.fontSize = '0.9rem';
    toast.style.transition = 'opacity 0.3s, transform 0.3s';
    toast.style.transform = 'translateY(20px)';
    toast.style.opacity = '0';
    toast.style.maxWidth = '300px';
    toast.innerHTML = '🔔 ' + message;
    
    toast.onclick = () => window.location.href = 'notifikasi.php';
    
    container.appendChild(toast);
    
    // Animasi masuk
    setTimeout(() => {
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
    }, 10);
    
    // Animasi keluar (hapus setelah 5 detik)
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}
</script>
<?php endif; ?>