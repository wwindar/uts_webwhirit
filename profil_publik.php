<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: katalog.php");
    exit();
}

// Redirect to own profile if clicking own id
if ($id == $_SESSION['user_id']) {
    header("Location: profil.php");
    exit();
}

$pageTitle = 'Profil Pengguna';
$basePath = '../';

// Ambil info user
$stmt = $conn->prepare("SELECT username, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$userResult = $stmt->get_result();
if ($userResult->num_rows === 0) {
    header("Location: katalog.php");
    exit();
}
$user = $userResult->fetch_assoc();
$stmt->close();

// Hitung resensi milik user ini saja
$stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM resensi WHERE user_id = ?");
$stmtCount->bind_param("i", $id);
$stmtCount->execute();
$totalResensi = $stmtCount->get_result()->fetch_assoc()['total'];
$stmtCount->close();

// Hitung rata-rata rating
$stmtAvg = $conn->prepare("SELECT AVG(rating) as avg_rating FROM resensi WHERE user_id = ?");
$stmtAvg->bind_param("i", $id);
$stmtAvg->execute();
$avgRating = round($stmtAvg->get_result()->fetch_assoc()['avg_rating'] ?? 0, 1);
$stmtAvg->close();

// Genre terbanyak
$stmtGenre = $conn->prepare(
    "SELECT genre, COUNT(*) as jml FROM resensi
     WHERE user_id = ? AND genre IS NOT NULL AND genre != ''
     GROUP BY genre ORDER BY jml DESC LIMIT 1"
);
$stmtGenre->bind_param("i", $id);
$stmtGenre->execute();
$favoriteGenre = $stmtGenre->get_result()->fetch_assoc()['genre'] ?? '-';
$stmtGenre->close();

// Ambil 5 resensi terbaru dari user ini
$stmtRecent = $conn->prepare("SELECT id, judul_buku, penulis, genre, rating, tgl_input FROM resensi WHERE user_id = ? ORDER BY tgl_input DESC LIMIT 5");
$stmtRecent->bind_param("i", $id);
$stmtRecent->execute();
$recentReviews = $stmtRecent->get_result();
$stmtRecent->close();

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
    <div style="margin-bottom:1.5rem">
        <a href="katalog.php" class="btn btn-outline btn-sm">← Kembali ke Katalog</a>
    </div>

    <div class="page-header">
        <h1>Profil Pengguna</h1>
        <p>Melihat profil dan koleksi resensi dari <strong><?= htmlspecialchars($user['username']) ?></strong>.</p>
    </div>

    <div style="display:grid;gap:1.5rem;align-items:start;grid-template-columns:1fr 2fr">

        <!-- ── Kartu Info Profil ── -->
        <div style="background:var(--paper);border:1px solid var(--border);border-top:3px solid var(--gold);
                    border-radius:4px;padding:1.8rem;box-shadow:0 4px 20px var(--shadow)">

            <div style="text-align:center;margin-bottom:1.5rem">
                <div style="width:72px;height:72px;background:var(--ink);border-radius:50%;
                            display:flex;align-items:center;justify-content:center;
                            margin:0 auto 0.75rem;font-size:2rem;border:3px solid var(--gold)">👤</div>
                <h2 style="font-family:var(--font-display);font-size:1.3rem;color:var(--ink)">
                    <?= htmlspecialchars($user['username']) ?>
                </h2>
                <span style="font-size:0.78rem;color:var(--brown);background:rgba(212,168,67,0.12);
                             border:1px solid rgba(212,168,67,0.3);border-radius:20px;padding:0.2rem 0.75rem">
                    Member
                </span>
            </div>

            <div style="border-top:1px solid var(--border);padding-top:1rem">
                <div style="margin-bottom:0.9rem">
                    <div style="font-size:0.75rem;font-weight:500;color:var(--ink-light);
                                text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem">Bergabung Sejak</div>
                    <div style="font-size:0.95rem;color:var(--ink)">
                        <?= date('d F Y', strtotime($user['created_at'])) ?>
                    </div>
                </div>

                <!-- Statistik -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.9rem">
                    <div style="background:var(--page-bg);border-radius:8px;padding:0.75rem;text-align:center">
                        <div style="font-size:1.8rem;font-family:var(--font-display);color:var(--gold);
                                    font-weight:700;line-height:1"><?= $totalResensi ?></div>
                        <div style="font-size:0.72rem;color:var(--ink-light);margin-top:0.2rem">Resensi Ditulis</div>
                    </div>
                    <div style="background:var(--page-bg);border-radius:8px;padding:0.75rem;text-align:center">
                        <div style="font-size:1.8rem;font-family:var(--font-display);color:var(--gold);
                                    font-weight:700;line-height:1"><?= $avgRating > 0 ? $avgRating : '-' ?></div>
                        <div style="font-size:0.72rem;color:var(--ink-light);margin-top:0.2rem">Rata-rata Rating</div>
                    </div>
                </div>
                <div style="margin-bottom:0.9rem">
                    <div style="font-size:0.75rem;font-weight:500;color:var(--ink-light);
                                text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem">Genre Favorit</div>
                    <div style="font-size:0.95rem;color:var(--ink)"><?= htmlspecialchars($favoriteGenre) ?></div>
                </div>
            </div>
        </div>

        <!-- ── Resensi Terbaru User Ini ── -->
        <div style="background:var(--paper);border:1px solid var(--border);border-top:3px solid #3498db;
                    border-radius:4px;padding:1.8rem;box-shadow:0 4px 20px var(--shadow)">
            <h2 style="font-family:var(--font-display);font-size:1.2rem;color:var(--ink);margin-bottom:1rem">
                📚 Resensi Terbaru oleh <?= htmlspecialchars($user['username']) ?>
            </h2>

            <?php if ($recentReviews->num_rows === 0): ?>
                <div class="empty-state" style="padding:2rem">
                    <div class="empty-icon">📭</div>
                    <h3>Belum ada resensi</h3>
                    <p>Pengguna ini belum menulis ulasan apapun.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Judul Buku</th>
                                <th>Genre</th>
                                <th>Rating</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $recentReviews->fetch_assoc()): ?>
                            <tr>
                                <td class="td-title" style="font-size:0.95rem">
                                    <?= htmlspecialchars($row['judul_buku']) ?><br>
                                    <small style="color:var(--ink-light);font-weight:normal">oleh <?= htmlspecialchars($row['penulis']) ?></small>
                                </td>
                                <td>
                                    <?php if ($row['genre']): ?>
                                        <span class="book-genre-badge"><?= htmlspecialchars($row['genre']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="td-rating"><?= renderStars($row['rating']) ?></td>
                                <td><?= date('d M Y', strtotime($row['tgl_input'])) ?></td>
                                <td>
                                    <a href="detail.php?id=<?= $row['id'] ?>" class="btn btn-outline btn-sm">Lihat</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include ('footer.php'); ?>
