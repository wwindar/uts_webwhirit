<?php

session_start();
require_once ('db.php');
require_once ('auth.php');

requireLogin();

$pageTitle = 'Dashboard';

$totalResensi = $conn->query("SELECT COUNT(*) as total FROM resensi")->fetch_assoc()['total'];
$avgRating    = $conn->query("SELECT ROUND(AVG(rating), 1) as avg FROM resensi")->fetch_assoc()['avg'] ?? 0;
$totalGenre   = $conn->query("SELECT COUNT(DISTINCT genre) as total FROM resensi WHERE genre IS NOT NULL")->fetch_assoc()['total'];
$topGenre     = $conn->query("SELECT genre, COUNT(*) as jml FROM resensi WHERE genre IS NOT NULL GROUP BY genre ORDER BY jml DESC LIMIT 1")->fetch_assoc();
$recentResult = $conn->query("SELECT r.*, u.username FROM resensi r JOIN users u ON r.user_id = u.id ORDER BY r.tgl_input DESC LIMIT 5");

function renderStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating ? '★' : '☆';
    }
    return $stars;
}
?>
<?php include ('header.php'); ?>

<div class="main-content">
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem">
        <div>
            <h1>Dashboard</h1>
            <p>Selamat datang, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> — ringkasan katalog resensi buku.</p>
        </div>
        <a href="tambah.php" class="btn btn-gold">+ Tambah Resensi</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📖</div>
            <div class="stat-num"><?= $totalResensi ?></div>
            <div class="stat-label">Total Resensi</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-num"><?= $avgRating ?: '-' ?></div>
            <div class="stat-label">Rata-rata Rating</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🏷️</div>
            <div class="stat-num"><?= $totalGenre ?></div>
            <div class="stat-label">Genre Tersedia</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🏆</div>
            <div class="stat-num" style="font-size:1.1rem;margin-top:0.4rem"><?= htmlspecialchars($topGenre['genre'] ?? '-') ?></div>
            <div class="stat-label">Genre Terbanyak</div>
        </div>
    </div>

    <h2 class="section-title">📚 Resensi Terbaru</h2>

    <?php if ($recentResult->num_rows === 0): ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3>Belum ada resensi</h3>
            <p>Mulai tambahkan resensi buku pertama Anda.</p>
            <a href="tambah.php" class="btn btn-gold" style="margin-top:1rem">+ Tambah Resensi</a>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Judul Buku</th>
                        <th>Penulis</th>
                        <th>Direview Oleh</th>
                        <th>Genre</th>
                        <th>Rating</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $recentResult->fetch_assoc()): ?>
                    <tr>
                        <td class="td-title"><?= htmlspecialchars($row['judul_buku']) ?></td>
                        <td><?= htmlspecialchars($row['penulis']) ?></td>
                        <td><a href="profil_publik.php?id=<?= $row['user_id'] ?>" style="color:var(--gold);text-decoration:none;font-weight:500"><?= htmlspecialchars($row['username']) ?></a></td>
                        <td>
                            <?php if ($row['genre']): ?>
                                <span class="book-genre-badge"><?= htmlspecialchars($row['genre']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="td-rating"><?= renderStars($row['rating']) ?></td>
                        <td><?= date('d M Y', strtotime($row['tgl_input'])) ?></td>
                        <td style="display:flex;gap:0.3rem">
                            <a href="detail.php?id=<?= $row['id'] ?>" class="btn btn-outline btn-sm">Lihat</a>
                            <?php if ($row['user_id'] == $_SESSION['user_id']): ?>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-gold btn-sm">Edit</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:1rem">
            <a href="katalog.php" class="btn btn-outline">Lihat Semua Resensi →</a>
        </div>
    <?php endif; ?>
</div>

<?php include ('footer.php'); ?>