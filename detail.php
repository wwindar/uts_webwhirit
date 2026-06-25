<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$pageTitle = 'Detail Resensi';
$basePath   = '../';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: katalog.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM resensi WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: katalog.php");
    exit();
}
$buku = $result->fetch_assoc();
$stmt->close();

function renderStars($rating) {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= $rating ? '★' : '☆';
    }
    return $out;
}
?>
<?php include ('header.php'); ?>

<div class="main-content">
    <div style="margin-bottom:1.5rem">
        <a href="katalog.php" class="btn btn-outline btn-sm">← Kembali ke Katalog</a>
    </div>

    <div class="detail-card">
        <div class="detail-header">
            <?php if ($buku['genre']): ?>
            <span class="book-genre-badge"><?= htmlspecialchars($buku['genre']) ?></span>
            <?php endif; ?>
            <h1 class="detail-title"><?= htmlspecialchars($buku['judul_buku']) ?></h1>
            <p class="detail-author">ditulis oleh <em><?= htmlspecialchars($buku['penulis']) ?></em></p>
        </div>

        <?php
        $fotoPath = !empty($buku['foto']) ? 'uploads/' . $buku['foto'] : '';
        $hasFoto  = $fotoPath && file_exists($fotoPath);
        ?>

        <div class="detail-body" style="<?= $hasFoto ? 'display:flex;gap:2rem;align-items:flex-start' : '' ?>">

            <?php if ($hasFoto): ?>
            <div class="detail-foto" style="flex-shrink:0">
                <img src="<?= htmlspecialchars($fotoPath) ?>"
                     alt="Cover <?= htmlspecialchars($buku['judul_buku']) ?>"
                     style="width:180px;max-height:260px;object-fit:cover;
                            border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.18);display:block;">
            </div>
            <?php endif; ?>

            <div style="flex:1;min-width:0">
                <div class="detail-stars">
                    <?= renderStars($buku['rating']) ?>
                    <small style="font-family:var(--font-body);font-size:0.85rem;color:var(--ink-light)">
                        (<?= $buku['rating'] ?>/5)
                    </small>
                </div>

                <blockquote class="detail-ulasan">
                    "<?= nl2br(htmlspecialchars($buku['ulasan'])) ?>"
                </blockquote>

                <div class="detail-meta">
                    Ditambahkan pada: <?= date('d F Y, H:i', strtotime($buku['tgl_input'])) ?> WIB
                </div>

                <div class="detail-actions">
                    <a href="edit.php?id=<?= $buku['id'] ?>" class="btn btn-gold">Edit Resensi</a>
                    <a href="hapus.php?id=<?= $buku['id'] ?>" class="btn btn-danger btn-hapus">Hapus</a>
                    <a href="katalog.php" class="btn btn-outline">Kembali</a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include ('footer.php'); ?>