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
if (isset($_SESSION['user_id']) && isset($conn)) {
    $uid = $_SESSION['user_id'];
    $u_res = $conn->query("SELECT username, foto_profil FROM users WHERE id = $uid");
    if ($u_res && $u_res->num_rows > 0) {
        $u_data = $u_res->fetch_assoc();
        $navUsername = $u_data['username'];
        if (!empty($u_data['foto_profil'])) $navUserFoto = $u_data['foto_profil'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>Katalog Resensi Buku</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">
        <span class="brand-icon">📚</span>
        <span class="brand-name">Resensi<em>Buku</em></span>
    </div>
    <button class="nav-toggle" id="navToggle">☰</button>
    <ul class="nav-links" id="navLinks">
        <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
        
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
                <hr class="nav-dropdown-divider">
                <a href="dm.php" class="dropdown-item">
                    💬 Kotak masuk
                    <?php if($unreadDmCount > 0) echo "<span class='dropdown-badge'>$unreadDmCount</span>"; ?>
                </a>
                <a href="notifikasi.php" class="dropdown-item">
                    🔔 Pembaruan
                    <?php if($notifCount > 0) echo "<span class='dropdown-badge'>$notifCount</span>"; ?>
                </a>
                <a href="katalog.php" class="dropdown-item">📚 Perpustakaan</a>
                <hr class="nav-dropdown-divider">
                <a href="wishlist.php" class="dropdown-item">📌 Wishlist</a>
                <a href="bookmarks.php" class="dropdown-item">🔖 Bookmark</a>
                <a href="pengguna.php" class="dropdown-item">👥 Pengguna</a>
                <a href="tambah.php" class="dropdown-item">➕ Tambah Resensi</a>
                <hr class="nav-dropdown-divider">
                <a href="about.php" class="dropdown-item">📖 Bantuan</a>
                <a href="logout.php" class="dropdown-item text-danger">🚪 Keluar</a>
            </div>
        </li>
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
</script>