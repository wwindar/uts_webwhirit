<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once('auth.php');
requireAdmin();

// Ambil info user untuk sidebar profil
$navUserFoto = 'default.png';
$navUsername = 'Admin';
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
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | Admin Dashboard' : 'Admin Dashboard' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🛠️</text></svg>">
</head>
<body class="admin-body">

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-header">
            <span class="admin-brand-icon">📚</span>
            <span class="admin-brand-name">Resensi<em>Admin</em></span>
            <button class="admin-sidebar-close" onclick="toggleAdminSidebar()">×</button>
        </div>
        
        <div class="admin-sidebar-profile">
            <?php if ($navUserFoto == 'default.png' || !file_exists('uploads/' . $navUserFoto)): ?>
                <div style="width: 45px; height: 45px; border-radius: 50%; border: 2px solid #d4a843; background: #2b2b30; color: #d4a843; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: bold; flex-shrink: 0;">
                    <?= strtoupper(substr($navUsername, 0, 1)) ?>
                </div>
            <?php else: ?>
                <img src="uploads/<?= htmlspecialchars($navUserFoto) ?>" alt="Admin">
            <?php endif; ?>
            <div class="admin-profile-info">
                <span class="admin-role">Administrator</span>
                <span class="admin-name">@<?= htmlspecialchars($navUsername) ?></span>
            </div>
        </div>

        <nav class="admin-sidebar-nav">
            <div class="admin-nav-label">MENU UTAMA</div>
            <a href="dashboard.php" class="admin-nav-link <?= (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : '' ?>">
                <span class="admin-nav-icon">📊</span> Dashboard
            </a>
            <a href="admin_users.php" class="admin-nav-link <?= (basename($_SERVER['PHP_SELF']) == 'admin_users.php') ? 'active' : '' ?>">
                <span class="admin-nav-icon">👥</span> Kelola Pengguna
            </a>
            
            <div class="admin-nav-label" style="margin-top: 1.5rem;">APLIKASI</div>
            <a href="katalog.php" class="admin-nav-link">
                <span class="admin-nav-icon">🌐</span> Katalog Web
            </a>
            <a href="logout.php" class="admin-nav-link text-danger" style="margin-top: auto;">
                <span class="admin-nav-icon">🚪</span> Keluar
            </a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <button class="admin-sidebar-toggle" onclick="toggleAdminSidebar()">☰</button>
            <div class="admin-topbar-title">
                <?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard' ?>
            </div>
        </header>

        <!-- Page Content -->
        <main class="admin-content">
