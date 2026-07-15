<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$pageTitle = 'Detail Resensi';
$basePath  = '../';
$userId    = $_SESSION['user_id'];

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: katalog.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM resensi WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { header("Location: katalog.php"); exit(); }
$buku = $result->fetch_assoc();
$stmt->close();

// Judul tab = judul buku yang sedang dibuka
$pageFullTitle = htmlspecialchars_decode($buku['judul_buku']) . ' | Resensi Buku';

// Hitung likes
$totalLikes = $conn->query("SELECT COUNT(*) as c FROM likes WHERE resensi_id = $id")->fetch_assoc()['c'];

// Cek sudah like?
$st = $conn->prepare("SELECT id FROM likes WHERE resensi_id = ? AND user_id = ?");
$st->bind_param("ii", $id, $userId);
$st->execute();
$sudahLike = $st->get_result()->num_rows > 0;
$st->close();

// Cek sudah bookmark?
$st = $conn->prepare("SELECT id FROM bookmarks WHERE resensi_id = ? AND user_id = ?");
$st->bind_param("ii", $id, $userId);
$st->execute();
$sudahBookmark = $st->get_result()->num_rows > 0;
$st->close();

// Komentar
$komentar = $conn->query(
    "SELECT k.id, k.resensi_id, k.user_id, k.isi_komentar AS isi, k.created_at AS tgl_komentar, u.username
     FROM komentar k
     JOIN users u ON u.id = k.user_id
     WHERE k.resensi_id = $id
     ORDER BY k.created_at ASC"
);

$flashMsg  = $_SESSION['flash']      ?? '';
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash'], $_SESSION['flash_type']);

function renderStars($rating) {
    $out = '';
    for ($i = 1; $i <= 5; $i++) $out .= $i <= $rating ? '★' : '☆';
    return $out;
}

$fotoPath = !empty($buku['foto']) ? 'uploads/' . $buku['foto'] : '';
$hasFoto  = $fotoPath && file_exists($fotoPath);
?>
<?php include ('header.php'); ?>

<div class="main-content">
    <div style="margin-bottom:1.5rem">
        <a href="katalog.php" class="btn btn-outline btn-sm">← Kembali ke Katalog</a>
    </div>

    <?php if ($flashMsg): ?>
    <div class="alert alert-<?= $flashType ?>"><?= htmlspecialchars($flashMsg) ?></div>
    <?php endif; ?>

    <div class="detail-card">
        <div class="detail-header">
            <?php if ($buku['genre']): ?>
            <span class="book-genre-badge"><?= htmlspecialchars($buku['genre']) ?></span>
            <?php endif; ?>
            <h1 class="detail-title"><?= htmlspecialchars($buku['judul_buku']) ?></h1>
            <p class="detail-author">ditulis oleh <em><?= htmlspecialchars($buku['penulis']) ?></em></p>
        </div>

        <div class="detail-body" style="<?= $hasFoto ? 'display:flex;flex-wrap:wrap;gap:2rem;align-items:flex-start' : '' ?>">

            <?php if ($hasFoto): ?>
            <div style="flex-shrink:0">
                <img src="<?= htmlspecialchars($fotoPath) ?>"
                     alt="Cover <?= htmlspecialchars($buku['judul_buku']) ?>"
                     style="width:180px;max-height:260px;object-fit:cover;
                            border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.18);display:block;">
            </div>
            <?php endif; ?>

            <div style="flex:1 1 280px;min-width:0">
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

                <!-- Aksi: Like, Bookmark, Lapor -->
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:1.25rem;margin-bottom:0.75rem;align-items:center">

                    <!-- Like -->
                    <form method="POST" action="likes.php" style="margin:0">
                        <input type="hidden" name="resensi_id" value="<?= $id ?>">
                        <input type="hidden" name="redirect" value="detail.php?id=<?= $id ?>">
                        <button type="submit" class="btn btn-sm <?= $sudahLike ? 'btn-primary' : 'btn-outline' ?>">
                            <?= $sudahLike ? '❤️' : '🤍' ?> <?= $totalLikes ?> Suka
                        </button>
                    </form>

                    <!-- Bookmark -->
                    <form method="POST" action="bookmarks.php" style="margin:0">
                        <input type="hidden" name="resensi_id" value="<?= $id ?>">
                        <input type="hidden" name="redirect" value="detail.php?id=<?= $id ?>">
                        <button type="submit" class="btn btn-sm <?= $sudahBookmark ? 'btn-gold' : 'btn-outline' ?>">
                            <?= $sudahBookmark ? '🔖 Disimpan' : '🔖 Simpan' ?>
                        </button>
                    </form>

                    <!-- Lapor -->
                    <button class="btn btn-sm btn-outline" style="margin-left:auto;color:var(--ink-light);font-size:0.8rem"
                        onclick="document.getElementById('form-lapor').style.display=
                                 document.getElementById('form-lapor').style.display==='none'?'block':'none'">
                        ⚑ Laporkan
                    </button>
                </div>

                <!-- Form Laporan (tersembunyi) -->
                <div id="form-lapor" style="display:none;background:var(--page-bg);border-radius:8px;padding:1rem;margin-bottom:1rem">
                    <form method="POST" action="laporan.php">
                        <input type="hidden" name="resensi_id" value="<?= $id ?>">
                        <input type="hidden" name="redirect" value="detail.php?id=<?= $id ?>">
                        <div class="form-group" style="margin-bottom:0.5rem">
                            <label style="font-size:0.85rem">Alasan laporan:</label>
                            <select name="alasan" required style="width:100%;padding:0.4rem 0.7rem;border-radius:6px;border:1px solid var(--border)">
                                <option value="">— Pilih alasan —</option>
                                <option value="Konten tidak pantas">Konten tidak pantas</option>
                                <option value="Informasi menyesatkan">Informasi menyesatkan</option>
                                <option value="Spam">Spam</option>
                                <option value="Plagiarisme">Plagiarisme</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm">Kirim Laporan</button>
                    </form>
                </div>

                <!-- Tombol Edit & Hapus (pemilik resensi) -->
                <?php if ($buku['user_id'] == $userId): ?>
                <div class="detail-actions">
                    <a href="edit.php?id=<?= $buku['id'] ?>" class="btn btn-gold">Edit Resensi</a>
                    <a href="hapus.php?id=<?= $buku['id'] ?>"
                       class="btn btn-danger btn-hapus"
                       onclick="return confirm('Yakin ingin menghapus resensi ini?')">Hapus</a>
                    <a href="katalog.php" class="btn btn-outline">Kembali</a>
                </div>
                <?php else: ?>
                <div class="detail-actions">
                    <a href="katalog.php" class="btn btn-outline">Kembali</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include ('_detail_body.inc.php'); ?>

    <!-- ===== KOMENTAR ===== -->
    <div id="komentar" class="form-card" style="margin-top:2rem">
        <h2 style="font-size:1.15rem;margin-bottom:1.25rem;font-family:var(--font-head)">
            💬 Komentar <small style="font-size:0.85rem;font-weight:400;color:var(--ink-light)">(<?= $komentar->num_rows ?>)</small>
        </h2>

        <!-- Daftar komentar -->
        <?php if ($komentar->num_rows === 0): ?>
        <p style="color:var(--ink-light);font-size:0.9rem;margin-bottom:1.25rem">Belum ada komentar. Jadilah yang pertama!</p>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:0.85rem;margin-bottom:1.5rem">
            <?php while ($k = $komentar->fetch_assoc()): ?>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.75rem;
                        border-left:3px solid var(--border);padding-left:0.85rem">
                <div style="flex:1;min-width:0">
                    <div style="font-weight:600;font-size:0.88rem">
                        <?= htmlspecialchars($k['username']) ?>
                        <span style="font-weight:400;color:var(--ink-light);margin-left:0.5rem;font-size:0.78rem">
                            <?= date('d M Y, H:i', strtotime($k['tgl_komentar'])) ?>
                        </span>
                    </div>
                    <div style="font-size:0.9rem;margin-top:0.25rem"><?= nl2br(htmlspecialchars($k['isi'])) ?></div>
                </div>
                <?php if ($k['user_id'] == $userId): ?>
                <form method="POST" action="komentar.php" style="margin:0;flex-shrink:0">
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="resensi_id" value="<?= $id ?>">
                    <input type="hidden" name="komentar_id" value="<?= $k['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm"
                        onclick="return confirm('Hapus komentar ini?')" title="Hapus komentar">×</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <!-- Form tambah komentar -->
        <form method="POST" action="komentar.php">
            <input type="hidden" name="action" value="tambah">
            <input type="hidden" name="resensi_id" value="<?= $id ?>">
            <div class="form-group" style="margin-bottom:0.75rem">
                <textarea name="isi" placeholder="Tulis komentarmu..." required
                    style="min-height:80px"><?= htmlspecialchars($_GET['komentar'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Kirim Komentar</button>
        </form>
    </div>
</div>

<?php include ('footer.php'); ?>