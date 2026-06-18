<?php
if (session_status() === PHP_SESSION_NONE) {
session_start();
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
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="stylesheet" href="/uts_webwhirit/style.css">
</head>
<body>

<nav id="navbar" class="navbar order-last order-lg-0">
<div class="nav-brand">
    <span class="brand-icon">📚</span>
    <span class="brand-name">Resensi<em>Buku</em></span>
</div>

<button class="nav-toggle" id="navToggle">☰</button>

<ul class="nav-links" id="navLinks">
    <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
    <li><a href="katalog.php" class="<?= basename($_SERVER['PHP_SELF']) == 'katalog.php' ? 'active' : '' ?>">Katalog</a></li>
    <li><a href="tambah.php" class="<?= basename($_SERVER['PHP_SELF']) == 'tambah.php' ? 'active' : '' ?>">+ Tambah Resensi</a></li>
    <li><a href="profil.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">👤 Profil</a></li>
    <li><a href="about.php" class="<?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : '' ?>">📖 About</a></li>
    <li><a href="logout.php" class="nav-logout">Keluar</a></li>
</ul>

<div class="header-social-links">
        <a href="https://twitter.com/@jeonkimchoi_" class="twitter" target="_blank"><i class="bi bi-twitter-x"></i></a>
        <a href="https://instagram.com/@wwinluvr_" class="instagram" target="_blank"><i class="bi bi-instagram"></i></a>
        <a href="https://tiktok.com/user/@wwinlvly_" class="tiktok" target="_blank"><i class="bi bi-tiktok"></i></a>
        <a href="https://wattpad.com/@hhongshi10_" class="wattpad" target="_blank"><i class="bi bi-book"></i></a>
    <?php if (isset($_SESSION['username'])): ?>
        <a href="logout.php" class="logout-link" title="Keluar">
        <i class="bi bi-box-arrow-right"></i>
        </a>
    <?php endif; ?>
</div>

<i class="bi bi-list mobile-nav-toggle"></i>
</nav>

</body>
</html>