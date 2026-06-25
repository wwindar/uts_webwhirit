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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>Katalog Resensi Buku</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/uts_webwhirit/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">
        <span class="brand-icon">📚</span>
        <span class="brand-name">Resensi<em>Buku</em></span>
    </div>
    <button class="nav-toggle" id="navToggle">☰</button>
    <ul class="nav-links" id="navLinks">
        <li><a href="dashboard.php"  class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php'  ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="katalog.php"    class="<?= basename($_SERVER['PHP_SELF']) == 'katalog.php'    ? 'active' : '' ?>">Katalog</a></li>
        <li><a href="tambah.php"     class="<?= basename($_SERVER['PHP_SELF']) == 'tambah.php'     ? 'active' : '' ?>">+ Tambah</a></li>
        <li><a href="wishlist.php"   class="<?= basename($_SERVER['PHP_SELF']) == 'wishlist.php'   ? 'active' : '' ?>">📌 Wishlist</a></li>
        <li><a href="bookmarks.php"  class="<?= basename($_SERVER['PHP_SELF']) == 'bookmarks.php'  ? 'active' : '' ?>">🔖 Bookmark</a></li>
        <li>
            <a href="notifikasi.php" class="<?= basename($_SERVER['PHP_SELF']) == 'notifikasi.php' ? 'active' : '' ?>"
               style="position:relative">
                🔔
                <?php if ($notifCount > 0): ?>
                <span style="position:absolute;top:-4px;right:-8px;background:#e74c3c;color:#fff;
                             border-radius:50%;width:16px;height:16px;font-size:10px;
                             display:flex;align-items:center;justify-content:center;font-weight:700">
                    <?= $notifCount > 9 ? '9+' : $notifCount ?>
                </span>
                <?php endif; ?>
            </a>
        </li>
        <li><a href="profil.php"     class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php'     ? 'active' : '' ?>">👤 Profil</a></li>
        <li><a href="about.php"      class="<?= basename($_SERVER['PHP_SELF']) == 'about.php'      ? 'active' : '' ?>">📖 About</a></li>
        <li><a href="logout.php" class="nav-logout">Keluar</a></li>
    </ul>
</nav>