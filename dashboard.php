<?php

session_start();
require_once ('db.php');
require_once ('auth.php');

requireLogin();

$pageTitle = 'Beranda';

$totalResensi = $conn->query("SELECT COUNT(*) as total FROM resensi")->fetch_assoc()['total'];
$avgRating    = $conn->query("SELECT ROUND(AVG(rating), 1) as avg FROM resensi")->fetch_assoc()['avg'] ?? 0;
$totalGenre   = $conn->query("SELECT COUNT(DISTINCT genre) as total FROM resensi WHERE genre IS NOT NULL")->fetch_assoc()['total'];
$topGenre     = $conn->query("SELECT genre, COUNT(*) as jml FROM resensi WHERE genre IS NOT NULL GROUP BY genre ORDER BY jml DESC LIMIT 1")->fetch_assoc();
$recentResult = $conn->query("SELECT r.*, u.username FROM resensi r JOIN users u ON r.user_id = u.id ORDER BY r.tgl_input DESC LIMIT 5");

// Chart 1: Jumlah resensi per genre
$chartQuery = $conn->query("SELECT genre, COUNT(*) as jml FROM resensi WHERE genre IS NOT NULL AND genre != '' GROUP BY genre");
$chartLabels = [];
$chartData = [];
while ($row = $chartQuery->fetch_assoc()) {
    $chartLabels[] = $row['genre'];
    $chartData[] = $row['jml'];
}
$chartLabelsJson = json_encode($chartLabels);
$chartDataJson = json_encode($chartData);

// Chart 2: Trend resensi per bulan (6 bulan terakhir)
$trendQuery = $conn->query("SELECT DATE_FORMAT(tgl_input, '%b %Y') as bulan, COUNT(*) as jml FROM resensi WHERE tgl_input >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(tgl_input, '%Y-%m') ORDER BY tgl_input ASC");
$trendLabels = []; $trendData = [];
while ($row = $trendQuery->fetch_assoc()) { $trendLabels[] = $row['bulan']; $trendData[] = $row['jml']; }
$trendLabelsJson = json_encode($trendLabels);
$trendDataJson = json_encode($trendData);

// Chart 3: Distribusi rating
$ratingQuery = $conn->query("SELECT rating, COUNT(*) as jml FROM resensi GROUP BY rating ORDER BY rating");
$ratingLabels = []; $ratingData = [];
while ($row = $ratingQuery->fetch_assoc()) { $ratingLabels[] = 'Rating '.$row['rating'].'⭐'; $ratingData[] = $row['jml']; }
$ratingLabelsJson = json_encode($ratingLabels);
$ratingDataJson = json_encode($ratingData);
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
        <a href="katalog.php" class="stat-card" style="text-decoration:none; color:inherit; display:block; transition:transform 0.2s" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='none'">
            <div class="stat-icon">📖</div>
            <div class="stat-num"><?= $totalResensi ?></div>
            <div class="stat-label">Total Resensi</div>
        </a>
        <a href="katalog.php?sort=rating_tinggi" class="stat-card" style="text-decoration:none; color:inherit; display:block; transition:transform 0.2s" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='none'">
            <div class="stat-icon">⭐</div>
            <div class="stat-num"><?= $avgRating ?: '-' ?></div>
            <div class="stat-label">Rata-rata Rating</div>
        </a>
        <a href="katalog.php" class="stat-card" style="text-decoration:none; color:inherit; display:block; transition:transform 0.2s" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='none'">
            <div class="stat-icon">🏷️</div>
            <div class="stat-num"><?= $totalGenre ?></div>
            <div class="stat-label">Genre Tersedia</div>
        </a>
        <a href="katalog.php?genre=<?= urlencode($topGenre['genre'] ?? '') ?>" class="stat-card" style="text-decoration:none; color:inherit; display:block; transition:transform 0.2s" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='none'">
            <div class="stat-icon">🏆</div>
            <div class="stat-num" style="font-size:1.1rem;margin-top:0.4rem"><?= htmlspecialchars($topGenre['genre'] ?? '-') ?></div>
            <div class="stat-label">Genre Terbanyak</div>
        </a>
    </div>

    <!-- Charts Grid -->
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin: 2rem 0;">
        <!-- Chart 1: Genre -->
        <div style="background:var(--paper); border:1px solid var(--border); border-radius:12px; padding:1.5rem; box-shadow:0 4px 20px var(--shadow); grid-column: span 2;">
            <h3 style="margin-bottom:1rem; font-family:var(--font-head); font-size:1.1rem; color:var(--ink);">📊 Distribusi Genre Resensi</h3>
            <div style="position:relative; height:260px; width:100%;">
                <canvas id="genreChart"></canvas>
            </div>
        </div>
        <!-- Chart 2: Trend Bulanan -->
        <div style="background:var(--paper); border:1px solid var(--border); border-radius:12px; padding:1.5rem; box-shadow:0 4px 20px var(--shadow);">
            <h3 style="margin-bottom:1rem; font-family:var(--font-head); font-size:1.1rem; color:var(--ink);">📈 Tren Resensi 6 Bulan</h3>
            <div style="position:relative; height:220px;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
        <!-- Chart 3: Distribusi Rating -->
        <div style="background:var(--paper); border:1px solid var(--border); border-radius:12px; padding:1.5rem; box-shadow:0 4px 20px var(--shadow);">
            <h3 style="margin-bottom:1rem; font-family:var(--font-head); font-size:1.1rem; color:var(--ink);">⭐ Distribusi Rating</h3>
            <div style="position:relative; height:220px; display:flex; justify-content:center;">
                <canvas id="ratingChart"></canvas>
            </div>
        </div>
    </div>

    <div style="margin: 2rem 0; background: var(--paper); border: 1px solid var(--border); border-top: 3px solid var(--gold); border-radius: 8px; padding: 1.5rem; box-shadow: 0 4px 20px var(--shadow)">
        <h3 style="margin-bottom: 0.75rem; font-family: var(--font-head); font-size: 1.15rem; color: var(--ink);">🔍 Pencarian</h3>
        <p style="color: var(--ink-light); font-size: 0.85rem; margin-bottom: 1rem;">Pilih kategori pencarian lalu masukkan nama buku, nama penulis, atau nama akun (username).</p>
        <form method="GET" action="katalog.php" id="form-cari-dashboard" style="display: flex; gap: 0.5rem; margin: 0; flex-wrap: wrap;" onsubmit="this.action = document.getElementById('jenis_cari').value;">
            <select id="jenis_cari" style="padding: 0.75rem; border: 1px solid var(--border); border-radius: 6px; font-family: inherit; font-size: 0.95rem; background: var(--page-bg); cursor: pointer;">
                <option value="katalog.php">Cari Resensi</option>
                <option value="pengguna.php">Cari Akun Pengguna</option>
            </select>
            <input type="text" name="search" placeholder="Ketik kata kunci di sini..." style="flex: 1; min-width: 200px; padding: 0.75rem; border: 1px solid var(--border); border-radius: 6px; font-family: inherit; font-size: 0.95rem;" required>
            <button type="submit" class="btn btn-primary" style="padding: 0 1.5rem; white-space: nowrap;">Cari</button>
        </form>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#e0d6cc' : '#3a2e28';
    const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.07)';

    // Chart 1: Genre Bar
    const ctx1 = document.getElementById('genreChart');
    const labels1 = <?= $chartLabelsJson ?>;
    const data1 = <?= $chartDataJson ?>;
    if (ctx1 && labels1.length > 0) {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: labels1,
                datasets: [{
                    label: 'Jumlah Resensi',
                    data: data1,
                    backgroundColor: ['rgba(163,93,108,0.75)','rgba(41,128,185,0.75)','rgba(39,174,96,0.75)','rgba(243,156,18,0.75)','rgba(142,68,173,0.75)','rgba(211,84,0,0.75)','rgba(22,160,133,0.75)'],
                    borderRadius: 6, borderWidth: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize:1, color: textColor }, grid: { color: gridColor } },
                    x: { ticks: { color: textColor }, grid: { display: false } }
                }
            }
        });
    } else if (ctx1) {
        ctx1.parentElement.innerHTML = '<p style="text-align:center;color:var(--ink-light);padding-top:2rem;">Belum ada data.</p>';
    }

    // Chart 2: Trend Line
    const ctx2 = document.getElementById('trendChart');
    const labels2 = <?= $trendLabelsJson ?>;
    const data2 = <?= $trendDataJson ?>;
    if (ctx2 && labels2.length > 0) {
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: labels2,
                datasets: [{
                    label: 'Resensi Baru',
                    data: data2,
                    borderColor: '#db2777',
                    backgroundColor: 'rgba(219,39,119,0.12)',
                    fill: true, tension: 0.4, pointRadius: 5,
                    pointBackgroundColor: '#db2777'
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize:1, color: textColor }, grid: { color: gridColor } },
                    x: { ticks: { color: textColor }, grid: { display: false } }
                }
            }
        });
    } else if (ctx2) {
        ctx2.parentElement.innerHTML = '<p style="text-align:center;color:var(--ink-light);padding-top:2rem;">Belum ada data 6 bulan terakhir.</p>';
    }

    // Chart 3: Rating Donut
    const ctx3 = document.getElementById('ratingChart');
    const labels3 = <?= $ratingLabelsJson ?>;
    const data3 = <?= $ratingDataJson ?>;
    if (ctx3 && labels3.length > 0) {
        new Chart(ctx3, {
            type: 'doughnut',
            data: {
                labels: labels3,
                datasets: [{
                    data: data3,
                    backgroundColor: ['#ef4444','#f97316','#eab308','#22c55e','#3b82f6'],
                    borderWidth: 2,
                    borderColor: isDark ? '#1a1410' : '#fff'
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: textColor, padding: 12, font: { size: 11 } } }
                }
            }
        });
    } else if (ctx3) {
        ctx3.parentElement.innerHTML = '<p style="text-align:center;color:var(--ink-light);padding-top:2rem;">Belum ada data rating.</p>';
    }
});
</script>

<?php include ('footer.php'); ?>