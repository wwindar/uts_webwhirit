<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$pageTitle = 'Bookmark Resensi';
$userId    = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resensiId = intval($_POST['resensi_id'] ?? 0);
    $redirect  = $_POST['redirect'] ?? "bookmarks.php";

    if ($resensiId > 0) {
        $st = $conn->prepare("SELECT id FROM bookmarks WHERE resensi_id = ? AND user_id = ?");
        $st->bind_param("ii", $resensiId, $userId);
        $st->execute();
        $sudah = $st->get_result()->num_rows > 0;
        $st->close();

        if ($sudah) {
            $st = $conn->prepare("DELETE FROM bookmarks WHERE resensi_id = ? AND user_id = ?");
            $st->bind_param("ii", $resensiId, $userId);
            $st->execute();
            $st->close();
            $_SESSION['flash'] = 'Bookmark dihapus.';
            $_SESSION['flash_type'] = 'info';
        } else {
            $st = $conn->prepare("INSERT INTO bookmarks (resensi_id, user_id) VALUES (?, ?)");
            $st->bind_param("ii", $resensiId, $userId);
            $st->execute();
            $st->close();
            $_SESSION['flash'] = 'Resensi disimpan ke bookmark! 🔖';
            $_SESSION['flash_type'] = 'success';
        }
    }
    header("Location: $redirect");
    exit();
}

$result = $conn->query(
    "SELECT r.*, b.created_at AS tgl_simpan
     FROM bookmarks b
     JOIN resensi r ON r.id = b.resensi_id
     WHERE b.user_id = $userId
     ORDER BY b.created_at DESC"
);

$flashMsg  = $_SESSION['flash']      ?? '';
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash'], $_SESSION['flash_type']);

function renderStars($rating) {
    $out = '';
    for ($i = 1; $i <= 5; $i++) $out .= $i <= $rating ? '★' : '☆';
    return $out;
}
?>
<?php include ('header.php'); ?>

<div class="main-content">
    <div class="page-header">
        <h1>🔖 Bookmark Resensi</h1>
        <p>Resensi yang kamu simpan untuk dibaca kembali.</p>
    </div>

    <?php if ($flashMsg): ?>
    <div class="alert alert-<?= $flashType ?>"><?= htmlspecialchars($flashMsg) ?></div>
    <?php endif; ?>

    <?php if ($result->num_rows === 0): ?>
    <div class="empty-state">
        <div class="empty-icon">🔖</div>
        <h3>Belum ada bookmark</h3>
        <p>Tekan ikon bookmark di halaman detail resensi untuk menyimpannya di sini.</p>
        <a href="katalog.php" class="btn btn-gold" style="margin-top:1rem">Lihat Katalog</a>
    </div>
    <?php else: ?>
    <p style="color:var(--ink-light);font-size:0.85rem;margin-bottom:1rem">
        <strong><?= $result->num_rows ?></strong> resensi tersimpan
    </p>
    <div class="books-grid">
        <?php while ($row = $result->fetch_assoc()): ?>
        <div class="book-card">
            <div class="book-card-spine"></div>
            <div class="book-card-body">
                <?php if ($row['genre']): ?>
                <span class="book-genre-badge"><?= htmlspecialchars($row['genre']) ?></span>
                <?php endif; ?>

                <?php if (!empty($row['foto']) && file_exists('uploads/' . $row['foto'])): ?>
                <img src="uploads/<?= htmlspecialchars($row['foto']) ?>" alt="Cover"
                    style="width:100%;max-height:160px;object-fit:cover;border-radius:6px;margin-bottom:0.5rem">
                <?php endif; ?>

                <div class="book-title"><?= htmlspecialchars($row['judul_buku']) ?></div>
                <div class="book-author">oleh <span><?= htmlspecialchars($row['penulis']) ?></span></div>
                <div class="book-ulasan"><?= htmlspecialchars(mb_substr($row['ulasan'], 0, 100)) ?>...</div>
                <div class="book-meta">
                    <span class="stars"><?= renderStars($row['rating']) ?></span>
                    <span class="book-date">Disimpan: <?= date('d M Y', strtotime($row['tgl_simpan'])) ?></span>
                </div>
                <div class="book-actions">
                    <a href="detail.php?id=<?= $row['id'] ?>" class="btn btn-outline btn-sm">Detail</a>
                    <form method="POST" style="display:inline;margin:0">
                        <input type="hidden" name="resensi_id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="redirect" value="bookmarks.php">
                        <button type="submit" class="btn btn-danger btn-sm"
                            onclick="return confirm('Hapus dari bookmark?')">🔖 Hapus</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<?php include ('footer.php'); ?>