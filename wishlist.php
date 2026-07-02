<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$pageTitle = 'Wishlist';
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'tambah') {
        $judulWish = trim($_POST['judul_buku'] ?? '');
        $penulisWish = trim($_POST['penulis'] ?? '');
        $catatan = trim($_POST['catatan'] ?? '');
        if ($judulWish !== '') {
            $penulisId = null;
            if ($penulisWish !== '') {
                $st = $conn->prepare("SELECT id FROM penulis WHERE nama = ?");
                $st->bind_param("s", $penulisWish);
                $st->execute();
                $res = $st->get_result();
                if ($row = $res->fetch_assoc()) {
                    $penulisId = $row['id'];
                } else {
                    $st2 = $conn->prepare("INSERT INTO penulis (nama) VALUES (?)");
                    $st2->bind_param("s", $penulisWish);
                    $st2->execute();
                    $penulisId = $st2->insert_id;
                    $st2->close();
                }
                $st->close();
            }

            $st = $conn->prepare("INSERT INTO wishlist (user_id, judul_buku, penulis_id, catatan) VALUES (?, ?, ?, ?)");
            $st->bind_param("isis", $userId, $judulWish, $penulisId, $catatan);
            $st->execute();
            $st->close();
            $_SESSION['flash'] = 'Buku berhasil ditambahkan ke wishlist!';
            $_SESSION['flash_type'] = 'success';
        }
    } elseif ($_POST['action'] === 'hapus') {
        $wid = intval($_POST['wishlist_id'] ?? 0);
        $st = $conn->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
        $st->bind_param("ii", $wid, $userId);
        $st->execute();
        $st->close();
        $_SESSION['flash'] = 'Buku dihapus dari wishlist.';
        $_SESSION['flash_type'] = 'info';
    } elseif ($_POST['action'] === 'selesai') {
        $wid = intval($_POST['wishlist_id'] ?? 0);
        $st = $conn->prepare("UPDATE wishlist SET sudah_baca = 1 WHERE id = ? AND user_id = ?");
        $st->bind_param("ii", $wid, $userId);
        $st->execute();
        $st->close();
        $_SESSION['flash'] = 'Yeay! Buku ditandai sudah dibaca ✅';
        $_SESSION['flash_type'] = 'success';
    }
    header("Location: wishlist.php");
    exit();
}

$filter = $_GET['filter'] ?? 'semua';
$extraWhere = match($filter) {
    'belum' => 'AND sudah_baca = 0',
    'sudah' => 'AND sudah_baca = 1',
    default => ''
};

$result = $conn->query(
    "SELECT w.id, w.user_id, w.judul_buku, w.penulis_id, w.genre_id, w.catatan, w.sudah_baca AS sudah_dibaca, w.created_at AS tgl_tambah, p.nama AS penulis
     FROM wishlist w
     LEFT JOIN penulis p ON p.id = w.penulis_id
     WHERE w.user_id = $userId $extraWhere
     ORDER BY w.created_at DESC"
);

$flashMsg  = $_SESSION['flash']      ?? '';
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash'], $_SESSION['flash_type']);
?>
<?php include ('header.php'); ?>

<div class="main-content">
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem">
        <div>
            <h1>📌 Wishlist Buku</h1>
            <p>Daftar buku yang ingin kamu baca.</p>
        </div>
        <button class="btn btn-gold" onclick="document.getElementById('form-tambah').style.display='block';this.style.display='none'">
            + Tambah Buku
        </button>
    </div>

    <?php if ($flashMsg): ?>
    <div class="alert alert-<?= $flashType ?>"><?= htmlspecialchars($flashMsg) ?></div>
    <?php endif; ?>

    <div id="form-tambah" class="form-card" style="display:none;margin-bottom:1.5rem">
        <h3 style="margin-bottom:1rem;font-size:1.1rem">Tambah ke Wishlist</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="tambah">
            <div class="form-row">
                <div class="form-group">
                    <label>Judul Buku <span style="color:#c0392b">*</span></label>
                    <input type="text" name="judul_buku" placeholder="Judul buku yang ingin dibaca" required maxlength="255">
                </div>
                <div class="form-group">
                    <label>Penulis</label>
                    <input type="text" name="penulis" placeholder="Nama penulis (opsional)" maxlength="100">
                </div>
            </div>
            <div class="form-group">
                <label>Catatan</label>
                <textarea name="catatan" placeholder="Kenapa ingin baca buku ini? (opsional)" style="min-height:80px"></textarea>
            </div>
            <div style="display:flex;gap:0.75rem">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <button type="button" class="btn btn-outline"
                    onclick="this.closest('#form-tambah').style.display='none';document.querySelector('.btn-gold').style.display=''">
                    Batal
                </button>
            </div>
        </form>
    </div>

    <div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
        <a href="wishlist.php?filter=semua" class="btn <?= $filter==='semua' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Semua</a>
        <a href="wishlist.php?filter=belum" class="btn <?= $filter==='belum' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Belum Dibaca</a>
        <a href="wishlist.php?filter=sudah" class="btn <?= $filter==='sudah' ? 'btn-gold' : 'btn-outline' ?> btn-sm">Sudah Dibaca ✅</a>
    </div>

    <?php if ($result->num_rows === 0): ?>
    <div class="empty-state">
        <div class="empty-icon">📌</div>
        <h3>Wishlist masih kosong</h3>
        <p>Tambahkan buku yang ingin kamu baca!</p>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:1rem">
        <?php while ($w = $result->fetch_assoc()): ?>
        <div class="form-card" style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;
             <?= $w['sudah_dibaca'] ? 'opacity:0.7;background:var(--page-bg)' : '' ?>">
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap">
                    <?php if ($w['sudah_dibaca']): ?>
                    <span style="background:#27ae60;color:#fff;border-radius:20px;padding:2px 10px;font-size:0.75rem">✅ Sudah dibaca</span>
                    <?php else: ?>
                    <span style="background:var(--ink-light);color:#fff;border-radius:20px;padding:2px 10px;font-size:0.75rem">📖 Belum dibaca</span>
                    <?php endif; ?>
                </div>
                <div style="font-weight:700;font-size:1.05rem;margin-top:0.4rem;font-family:var(--font-head)">
                    <?= htmlspecialchars($w['judul_buku']) ?>
                </div>
                <?php if ($w['penulis']): ?>
                <div style="font-size:0.85rem;color:var(--ink-light)">oleh <?= htmlspecialchars($w['penulis']) ?></div>
                <?php endif; ?>
                <?php if ($w['catatan']): ?>
                <div style="font-size:0.85rem;margin-top:0.4rem;color:var(--ink-mid);font-style:italic">
                    "<?= htmlspecialchars($w['catatan']) ?>"
                </div>
                <?php endif; ?>
                <div style="font-size:0.75rem;color:var(--ink-light);margin-top:0.4rem">
                    Ditambahkan: <?= date('d M Y', strtotime($w['tgl_tambah'])) ?>
                </div>
            </div>
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:flex-start">
                <?php if (!$w['sudah_dibaca']): ?>
                <form method="POST" style="margin:0">
                    <input type="hidden" name="action" value="selesai">
                    <input type="hidden" name="wishlist_id" value="<?= $w['id'] ?>">
                    <button type="submit" class="btn btn-outline btn-sm" title="Tandai sudah dibaca">✅</button>
                </form>
                <?php endif; ?>
                <form method="POST" style="margin:0"
                    onsubmit="return confirm('Hapus dari wishlist?')">
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="wishlist_id" value="<?= $w['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                </form>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<?php include ('footer.php'); ?>