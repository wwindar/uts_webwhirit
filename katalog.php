<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$pageTitle = 'Katalog';
$basePath  = '../';
$userId    = $_SESSION['user_id'];

// ── Handle POST edit dari modal ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $editId  = intval($_POST['edit_id']);
    $judul   = trim($_POST['judul_buku'] ?? '');
    $penulis = trim($_POST['penulis']    ?? '');
    $ulasan  = trim($_POST['ulasan']     ?? '');
    $rating  = intval($_POST['rating']   ?? 0);

    $genreSelect = $_POST['genre']        ?? '';
    $genreCustom = trim($_POST['genre_custom'] ?? '');
    $genre = ($genreSelect === 'Lainnya' && $genreCustom !== '')
        ? $genreCustom
        : $genreSelect;

    $errors = [];
    if (empty($judul))            $errors[] = 'Judul wajib diisi.';
    if (empty($penulis))          $errors[] = 'Penulis wajib diisi.';
    if (empty($ulasan))           $errors[] = 'Ulasan wajib diisi.';
    if ($rating < 1 || $rating > 5) $errors[] = 'Rating wajib dipilih.';

    // Ambil foto lama
    $stOld = $conn->prepare("SELECT foto FROM resensi WHERE id = ?");
    $stOld->bind_param("i", $editId);
    $stOld->execute();
    $fotoLama = $stOld->get_result()->fetch_assoc()['foto'] ?? null;
    $stOld->close();
    $fotoNama = $fotoLama;

    // Upload foto baru (jika ada)
    if (!empty($_FILES['foto']['name'])) {
        $allowedTypes = ['image/jpeg','image/png','image/gif','image/webp'];
        $maxSize      = 2 * 1024 * 1024;
        if (!in_array($_FILES['foto']['type'], $allowedTypes)) {
            $errors[] = 'Format foto tidak didukung.';
        } elseif ($_FILES['foto']['size'] > $maxSize) {
            $errors[] = 'Ukuran foto maks 2 MB.';
        } else {
            $ext     = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $newFoto = 'foto_' . time() . '_' . rand(100,999) . '.' . $ext;
            $dir     = 'uploads/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $newFoto)) {
                if ($fotoLama && file_exists($dir . $fotoLama)) @unlink($dir . $fotoLama);
                $fotoNama = $newFoto;
            } else {
                $errors[] = 'Gagal upload foto.';
            }
        }
    }

    // Hapus foto
    if (isset($_POST['hapus_foto']) && $_POST['hapus_foto'] === '1') {
        $dir = 'uploads/';
        if ($fotoLama && file_exists($dir . $fotoLama)) @unlink($dir . $fotoLama);
        $fotoNama = null;
    }

    if (empty($errors)) {
        $st = $conn->prepare(
            "UPDATE resensi SET judul_buku=?, penulis=?, genre=?, ulasan=?, rating=?, foto=? WHERE id=?"
        );
        $st->bind_param("ssssisi", $judul, $penulis, $genre, $ulasan, $rating, $fotoNama, $editId);
        $st->execute();
        $st->close();
        $_SESSION['flash']      = 'Resensi "' . $judul . '" berhasil diperbarui!';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash']      = implode(' ', $errors);
        $_SESSION['flash_type'] = 'error';
    }
    header("Location: katalog.php");
    exit();
}

// ── Query utama ──────────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$genre  = trim($_GET['genre']  ?? '');
$sort   = $_GET['sort'] ?? 'terbaru';

$where  = []; $params = []; $types = '';

if ($search !== '') {
    $where[] = "(judul_buku LIKE ? OR penulis LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like;
    $types .= 'ss';
}
if ($genre !== '') {
    $where[] = "genre = ?";
    $params[] = $genre;
    $types .= 's';
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$orderClause = match($sort) {
    'judul'        => 'ORDER BY judul_buku ASC',
    'rating_tinggi'=> 'ORDER BY rating DESC',
    'rating_rendah'=> 'ORDER BY rating ASC',
    default        => 'ORDER BY tgl_input DESC'
};

$sql  = "SELECT r.*, u.username FROM resensi r JOIN users u ON r.user_id = u.id $whereClause $orderClause";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$genreResult = $conn->query(
    "SELECT DISTINCT genre FROM resensi WHERE genre IS NOT NULL AND genre != '' ORDER BY genre"
);

$flashMsg  = $_SESSION['flash']      ?? '';
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash'], $_SESSION['flash_type']);

function renderStars($rating) {
    $s = '';
    for ($i = 1; $i <= 5; $i++) $s .= $i <= $rating ? '★' : '☆';
    return $s;
}

$genreOptions = [
    'Fiksi Ilmiah','Horor','Bromance','Fiksi Remaja','Supranatural',
    'Omegaverse','Romantic Comedy','Angst','Biografi','Filsafat',
    'Hurt/Comfort','Local/Lokal AU','Family','Friendship','Novel',
    'Puisi','Cerpen','Sejarah','Romance','Thriller','Mystery',
    'Fantasy','Science Fiction','Slice of Life','Young Adult','Adult',
    'Childrens Literature','Urban Fantasy','Historical','Dystopian',
    'Contemporary','Adventure','Thriller & Mystery','Lainnya'
];

// Ambil semua resensi ke array untuk modal (agar bisa di-pass ke JS)
$allResensi = [];
$result->data_seek(0);
while ($r = $result->fetch_assoc()) $allResensi[$r['id']] = $r;
$result->data_seek(0);
?>
<?php include ('header.php'); ?>

<div class="main-content">
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem">
        <div>
            <h1>Katalog Resensi</h1>
            <p>Seluruh koleksi ulasan buku yang telah ditambahkan.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="ekspor_csv.php" class="btn btn-outline" style="border-color:#27ae60;color:#27ae60" title="Ekspor CSV">⬇ CSV</a>
            <a href="ekspor_excel.php" class="btn btn-outline" style="border-color:#27ae60;color:#27ae60" title="Ekspor Excel">⬇ Excel</a>
            <a href="ekspor_word.php" class="btn btn-outline" style="border-color:#2980b9;color:#2980b9" title="Ekspor Word">⬇ Word</a>
            <button class="btn btn-outline" style="border-color:#8e44ad;color:#8e44ad" onclick="bukaModalImpor()" title="Impor CSV">⬆ Impor</button>
            <a href="tambah.php" class="btn btn-gold">+ Tambah Resensi</a>
        </div>
    </div>

    <?php if ($flashMsg): ?>
    <div class="alert alert-<?= $flashType ?>"><?= htmlspecialchars($flashMsg) ?></div>
    <?php endif; ?>

    <form method="GET" action="">
        <div class="filter-bar">
            <input type="text" name="search" placeholder="🔍 Cari judul atau penulis..."
                value="<?= htmlspecialchars($search) ?>">
            <select name="genre">
                <option value="">Semua Genre</option>
                <?php
                $genreResult->data_seek(0);
                while ($g = $genreResult->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($g['genre']) ?>"
                    <?= $genre === $g['genre'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['genre']) ?>
                </option>
                <?php endwhile; ?>
            </select>
            <select name="sort">
                <option value="terbaru"      <?= $sort==='terbaru'       ? 'selected':'' ?>>Terbaru</option>
                <option value="judul"        <?= $sort==='judul'         ? 'selected':'' ?>>A–Z Judul</option>
                <option value="rating_tinggi"<?= $sort==='rating_tinggi' ? 'selected':'' ?>>Rating Tertinggi</option>
                <option value="rating_rendah"<?= $sort==='rating_rendah' ? 'selected':'' ?>>Rating Terendah</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($search || $genre): ?>
            <a href="katalog.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (count($allResensi) === 0): ?>
    <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <h3>Tidak ada resensi ditemukan</h3>
        <p><?= $search || $genre ? 'Coba kata kunci atau filter yang berbeda.' : 'Jadilah yang pertama menambahkan resensi!' ?></p>
        <a href="tambah.php" class="btn btn-gold" style="margin-top:1rem">+ Tambah Resensi</a>
    </div>
    <?php else: ?>
    <p style="color:var(--ink-light);font-size:0.85rem;margin-bottom:1rem">
        Menampilkan <strong><?= count($allResensi) ?></strong> resensi
    </p>
    <div class="books-grid">
        <?php foreach ($allResensi as $row): ?>
        <div class="book-card">
            <div class="book-card-spine"></div>
            <div class="book-card-body">
                <?php if ($row['genre']): ?>
                <span class="book-genre-badge"><?= htmlspecialchars($row['genre']) ?></span>
                <?php endif; ?>

                <?php if (!empty($row['foto']) && file_exists('uploads/' . $row['foto'])): ?>
                <img src="uploads/<?= htmlspecialchars($row['foto']) ?>" alt="Cover"
                    style="width:100%;max-height:160px;object-fit:cover;border-radius:6px;margin-bottom:0.5rem;display:block">
                <?php endif; ?>

                <div class="book-title"><?= htmlspecialchars($row['judul_buku']) ?></div>
                <div class="book-author" style="margin-bottom:0.3rem">oleh <span><?= htmlspecialchars($row['penulis']) ?></span></div>
                <div style="font-size:0.8rem;color:var(--ink-light);margin-bottom:0.75rem">
                    👤 <a href="profil_publik.php?id=<?= $row['user_id'] ?>" style="color:var(--gold);text-decoration:none;font-weight:600"><?= htmlspecialchars($row['username']) ?></a>
                </div>
                <div class="book-ulasan"><?= htmlspecialchars($row['ulasan']) ?></div>
                <div class="book-meta">
                    <span class="stars"><?= renderStars($row['rating']) ?></span>
                    <span class="book-date"><?= date('d M Y', strtotime($row['tgl_input'])) ?></span>
                </div>
                <div class="book-actions">
                    <a href="detail.php?id=<?= $row['id'] ?>" class="btn btn-outline btn-sm">Detail</a>
                    <?php if ($row['user_id'] == $userId): ?>
                    <button class="btn btn-gold btn-sm" onclick="bukaModal(<?= $row['id'] ?>)">Edit</button>
                    <a href="hapus.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm btn-hapus" onclick="return confirm('Yakin hapus resensi ini?')">Hapus</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ══════════════ MODAL EDIT ══════════════ -->
<div id="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;
     overflow-y:auto;padding:2rem 1rem" onclick="if(event.target===this)tutupModal()">
    <div style="background:#fff;border-radius:16px;max-width:640px;margin:auto;padding:2rem;position:relative">

        <!-- Tombol tutup -->
        <button onclick="tutupModal()" style="position:absolute;top:1rem;right:1rem;
            background:none;border:none;font-size:1.4rem;cursor:pointer;color:#888;line-height:1">×</button>

        <h2 style="font-family:var(--font-head);font-size:1.3rem;margin-bottom:1.5rem">✏️ Edit Resensi</h2>

        <form id="form-edit" method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="edit_id" id="edit_id">

            <div class="form-row">
                <div class="form-group">
                    <label>JUDUL BUKU <span style="color:#c0392b">*</span></label>
                    <input type="text" name="judul_buku" id="m_judul" required maxlength="255">
                </div>
                <div class="form-group">
                    <label>PENULIS <span style="color:#c0392b">*</span></label>
                    <input type="text" name="penulis" id="m_penulis" required maxlength="100">
                </div>
            </div>

            <!-- GENRE -->
            <div class="form-group">
                <label>GENRE</label>
                <select name="genre" id="m_genre" onchange="toggleGenreModal(this.value)">
                    <option value="">— Pilih Genre —</option>
                    <?php foreach ($genreOptions as $g): ?>
                    <option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" id="m_genre_custom_wrap" style="display:none;margin-top:-0.5rem">
                <label>Tulis genre sendiri</label>
                <input type="text" name="genre_custom" id="m_genre_custom"
                    placeholder="Contoh: Romance, Thriller / Angst, Hurt/Comfort" maxlength="100">
                <small style="color:var(--ink-light);font-size:0.8rem">Pisahkan dengan koma atau garis miring untuk double genre.</small>
            </div>

            <!-- FOTO -->
            <div class="form-group">
                <label>FOTO SAMPUL / COVER</label>

                <!-- Foto saat ini (ditampilkan via JS) -->
                <div id="m_foto_current" style="display:none;margin-bottom:0.75rem">
                    <p style="font-size:0.82rem;color:var(--ink-light);margin-bottom:0.3rem">Foto saat ini:</p>
                    <img id="m_foto_img" src="" alt="Cover saat ini"
                        style="max-width:130px;max-height:180px;object-fit:cover;border-radius:8px;
                               box-shadow:0 2px 8px rgba(0,0,0,.15);display:block;margin-bottom:0.4rem">
                    <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.82rem;cursor:pointer">
                        <input type="checkbox" name="hapus_foto" value="1" id="m_hapus_foto"
                            onchange="toggleHapusFotoModal(this)">
                        Hapus foto ini
                    </label>
                </div>

                <label style="font-size:0.88rem;display:block;margin-bottom:0.3rem" id="m_foto_label">
                    Unggah foto (opsional):
                </label>
                <input type="file" name="foto" id="m_foto_input"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    onchange="previewFotoModal(this)">
                <small style="color:var(--ink-light);font-size:0.78rem">JPG, PNG, GIF, WEBP — maks 2 MB</small>

                <!-- Preview foto baru -->
                <div id="m_foto_preview" style="display:none;margin-top:0.6rem">
                    <img id="m_preview_img" src="" alt="Preview"
                        style="max-width:130px;max-height:180px;object-fit:cover;border-radius:8px;
                               box-shadow:0 2px 8px rgba(0,0,0,.15)">
                </div>
            </div>

            <!-- ULASAN -->
            <div class="form-group">
                <label>ULASAN / RESENSI <span style="color:#c0392b">*</span></label>
                <textarea name="ulasan" id="m_ulasan" required style="min-height:130px"></textarea>
            </div>

            <!-- RATING -->
            <div class="form-group">
                <label>RATING <span style="color:#c0392b">*</span></label>
                <div class="star-select" id="m_star_select">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" id="m_rating<?= $i ?>" name="rating" value="<?= $i ?>">
                    <label for="m_rating<?= $i ?>">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <div style="display:flex;gap:0.75rem;margin-top:0.5rem">
                <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
                <button type="button" class="btn btn-outline" onclick="tutupModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════ MODAL IMPOR ══════════════ -->
<div id="modal-impor-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;
     overflow-y:auto;padding:2rem 1rem" onclick="if(event.target===this)tutupModalImpor()">
    <div style="background:#fff;border-radius:16px;max-width:500px;margin:auto;padding:2rem;position:relative;border-top:3px solid #2980b9">
        <button onclick="tutupModalImpor()" style="position:absolute;top:1rem;right:1rem;
            background:none;border:none;font-size:1.4rem;cursor:pointer;color:#888;line-height:1">×</button>
        <h2 style="font-family:var(--font-head);font-size:1.3rem;margin-bottom:0.5rem">📥 Impor Data Resensi</h2>
        <p style="color:var(--ink-light);font-size:0.85rem;margin-bottom:1.25rem;padding-bottom:1rem;border-bottom:1px solid var(--border)">
            Unggah file CSV untuk menambahkan resensi secara masal.
        </p>
        <form method="POST" action="impor_csv.php" enctype="multipart/form-data">
            <div class="form-group" style="margin-bottom:1rem">
                <label>Pilih File CSV <span style="color:#c0392b">*</span></label>
                <input type="file" name="file_csv" accept=".csv" required style="padding:0.4rem 0; width:100%">
                <small style="color:var(--ink-light);font-size:0.78rem;display:block;margin-top:0.3rem">
                    Kolom minimum: <strong>Judul Buku</strong> dan <strong>Ulasan</strong>. Format harus sesuai (bisa gunakan hasil Ekspor CSV sebagai template).
                </small>
            </div>
            <div style="display:flex;gap:0.75rem;margin-top:1rem">
                <button type="submit" class="btn btn-primary" style="background:#2980b9;border-color:#2980b9">⬆ Unggah & Impor</button>
                <button type="button" class="btn btn-outline" onclick="tutupModalImpor()">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Data resensi untuk JS -->
<script>
const resensiData = <?= json_encode(array_values($allResensi), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function bukaModal(id) {
    const r = resensiData.find(x => x.id == id);
    if (!r) return;

    document.getElementById('edit_id').value   = r.id;
    document.getElementById('m_judul').value   = r.judul_buku;
    document.getElementById('m_penulis').value = r.penulis;
    document.getElementById('m_ulasan').value  = r.ulasan;

    // Rating
    const ratingInput = document.getElementById('m_rating' + r.rating);
    if (ratingInput) ratingInput.checked = true;

    // Genre — cek apakah ada di list atau custom
    const genreSelect = document.getElementById('m_genre');
    const genreCustom = document.getElementById('m_genre_custom');
    const customWrap  = document.getElementById('m_genre_custom_wrap');
    let genreFound = false;
    for (let opt of genreSelect.options) {
        if (opt.value === r.genre) { opt.selected = true; genreFound = true; break; }
    }
    if (!genreFound && r.genre && r.genre !== '') {
        genreSelect.value      = 'Lainnya';
        genreCustom.value      = r.genre;
        customWrap.style.display = 'block';
    } else {
        genreCustom.value      = '';
        customWrap.style.display = genreSelect.value === 'Lainnya' ? 'block' : 'none';
    }

    // Foto
    const fotoWrap    = document.getElementById('m_foto_current');
    const fotoImg     = document.getElementById('m_foto_img');
    const fotoLabel   = document.getElementById('m_foto_label');
    const hapusCb     = document.getElementById('m_hapus_foto');
    const fotoInput   = document.getElementById('m_foto_input');
    const previewWrap = document.getElementById('m_foto_preview');

    hapusCb.checked        = false;
    fotoInput.disabled     = false;
    fotoInput.value        = '';
    previewWrap.style.display = 'none';

    if (r.foto) {
        fotoImg.src            = 'uploads/' + r.foto;
        fotoWrap.style.display = 'block';
        fotoLabel.textContent  = 'Ganti dengan foto baru (opsional):';
    } else {
        fotoWrap.style.display = 'none';
        fotoLabel.textContent  = 'Unggah foto (opsional):';
    }

    document.getElementById('modal-overlay').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function tutupModal() {
    document.getElementById('modal-overlay').style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('form-edit').reset();
    document.getElementById('m_foto_current').style.display = 'none';
    document.getElementById('m_foto_preview').style.display = 'none';
    document.getElementById('m_genre_custom_wrap').style.display = 'none';
}

function toggleGenreModal(val) {
    const wrap = document.getElementById('m_genre_custom_wrap');
    wrap.style.display = (val === 'Lainnya') ? 'block' : 'none';
    if (val !== 'Lainnya') document.getElementById('m_genre_custom').value = '';
}

function toggleHapusFotoModal(cb) {
    const fotoInput   = document.getElementById('m_foto_input');
    const previewWrap = document.getElementById('m_foto_preview');
    if (cb.checked) {
        fotoInput.value        = '';
        fotoInput.disabled     = true;
        previewWrap.style.display = 'none';
    } else {
        fotoInput.disabled = false;
    }
}

function previewFotoModal(input) {
    const wrap = document.getElementById('m_foto_preview');
    const img  = document.getElementById('m_preview_img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; wrap.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    } else {
        wrap.style.display = 'none';
    }
}

// Tutup modal dengan ESC
document.addEventListener('keydown', e => { 
    if (e.key === 'Escape') {
        tutupModal();
        tutupModalImpor();
    }
});

function bukaModalImpor() {
    document.getElementById('modal-impor-overlay').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function tutupModalImpor() {
    document.getElementById('modal-impor-overlay').style.display = 'none';
    document.body.style.overflow = '';
}
</script>

<?php include ('footer.php'); ?>