<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$pageTitle = 'Tulis Resensi';
$basePath   = '../';
$errors     = [];
$old        = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old     = $_POST;
    $judul   = trim($_POST['judul_buku'] ?? '');
    $penulis = trim($_POST['penulis']    ?? '');
    $ulasan  = trim($_POST['ulasan']     ?? '');
    $rating  = intval($_POST['rating']   ?? 0);
    $link_tokopedia = trim($_POST['link_tokopedia'] ?? '');
    $link_shopee    = trim($_POST['link_shopee']    ?? '');
    $link_gramedia  = trim($_POST['link_gramedia']  ?? '');

    // Genre: jika pilih "Lainnya", pakai input teks custom
    $genreSelect = $_POST['genre']        ?? '';
    $genreCustom = trim($_POST['genre_custom'] ?? '');
    $genre = ($genreSelect === 'Lainnya' && $genreCustom !== '')
        ? $genreCustom
        : $genreSelect;

    if (empty($judul))              $errors[] = 'Judul buku wajib diisi.';
    elseif (strlen($judul) > 255)   $errors[] = 'Judul buku maksimal 255 karakter.';

    if (empty($penulis))            $errors[] = 'Nama penulis wajib diisi.';
    elseif (strlen($penulis) > 100) $errors[] = 'Nama penulis maksimal 100 karakter.';

    if (empty($ulasan))             $errors[] = 'Ulasan wajib diisi.';
    elseif (strlen($ulasan) < 20)   $errors[] = 'Ulasan minimal 20 karakter.';

    if ($rating < 1 || $rating > 5) $errors[] = 'Rating wajib dipilih (1-5 bintang).';

    // Handle upload foto
    $fotoNama = null;
    if (!empty($_FILES['foto']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize      = 2 * 1024 * 1024; // 2 MB

        if (!in_array($_FILES['foto']['type'], $allowedTypes)) {
            $errors[] = 'Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.';
        } elseif ($_FILES['foto']['size'] > $maxSize) {
            $errors[] = 'Ukuran foto maksimal 2 MB.';
        } else {
            $ext      = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotoNama = 'foto_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $fotoNama)) {
                $errors[] = 'Gagal mengunggah foto. Silakan coba lagi.';
                $fotoNama = null;
            }
        }
    }

    if (empty($errors)) {
        $userId = $_SESSION['user_id'];
        $stmt   = $conn->prepare(
            "INSERT INTO resensi (judul_buku, penulis, genre, ulasan, rating, foto, user_id, link_tokopedia, link_shopee, link_gramedia)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssisisss", $judul, $penulis, $genre, $ulasan, $rating, $fotoNama, $userId, $link_tokopedia, $link_shopee, $link_gramedia);

        if ($stmt->execute()) {
            $_SESSION['flash']      = 'Resensi "' . $judul . '" berhasil ditambahkan!';
            $_SESSION['flash_type'] = 'success';
            header("Location: katalog.php");
            exit();
        } else {
            $errors[] = 'Gagal menyimpan data. Silakan coba lagi.';
        }
        $stmt->close();
    }
}

$genreOptions = [
    'Fiksi Ilmiah', 'Horor', 'Bromance', 'Fiksi Remaja', 'Supranatural',
    'Omegaverse', 'Romantic Comedy', 'Angst', 'Biografi', 'Filsafat',
    'Hurt/Comfort', 'Local/Lokal AU', 'Family', 'Friendship', 'Novel',
    'Puisi', 'Cerpen', 'Sejarah', 'Romance', 'Thriller', 'Mystery',
    'Fantasy', 'Science Fiction', 'Slice of Life', 'Young Adult', 'Adult',
    'Childrens Literature', 'Urban Fantasy', 'Historical', 'Dystopian',
    'Contemporary', 'Adventure', 'Thriller & Mystery', 'Lainnya'
];
?>
<?php include ('header.php'); ?>

<div class="main-content">
    <div class="page-header">
        <h1>Tambah Resensi</h1>
        <p>Tulis ulasan buku baru untuk koleksi katalog.</p>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-error">
        <strong>Mohon periksa kembali:</strong><br>
        <?php foreach ($errors as $e): ?>
            • <?= htmlspecialchars($e) ?><br>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" action="" enctype="multipart/form-data">

            <div class="form-row">
                <div class="form-group">
                    <label for="judul_buku">Judul Buku <span style="color:#c0392b">*</span></label>
                    <input type="text" id="judul_buku" name="judul_buku"
                        value="<?= htmlspecialchars($old['judul_buku'] ?? '') ?>"
                        placeholder="Contoh: Not The Best, But Still Good" required maxlength="255">
                </div>
                <div class="form-group">
                    <label for="penulis">Penulis <span style="color:#c0392b">*</span></label>
                    <input type="text" id="penulis" name="penulis"
                        value="<?= htmlspecialchars($old['penulis'] ?? '') ?>"
                        placeholder="Contoh: peachhplease" required maxlength="100">
                </div>
            </div>

            <!-- GENRE -->
            <div class="form-group">
                <label for="genre">Genre</label>
                <select id="genre" name="genre" onchange="toggleGenreCustom(this.value)">
                    <option value="">— Pilih Genre —</option>
                    <?php foreach ($genreOptions as $g): ?>
                    <option value="<?= htmlspecialchars($g) ?>"
                        <?= ($old['genre'] ?? '') === $g ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Input genre custom (muncul jika pilih Lainnya) -->
            <div class="form-group" id="genre-custom-wrap" style="display:none;margin-top:-0.5rem">
                <label for="genre_custom">Tulis genre sendiri</label>
                <input type="text" id="genre_custom" name="genre_custom"
                    value="<?= htmlspecialchars($old['genre_custom'] ?? '') ?>"
                    placeholder="Contoh: Romance, Thriller / Angst, Hurt/Comfort"
                    maxlength="100">
                <small style="color:var(--ink-light);font-size:0.8rem">
                    Bisa tulis lebih dari satu genre, pisahkan dengan koma atau garis miring.
                </small>
            </div>

            <!-- FOTO -->
            <div class="form-group">
                <label for="foto">Foto Sampul / Cover Buku</label>
                <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/gif,image/webp"
                    onchange="previewFoto(this)">
                <small style="color:var(--ink-light);font-size:0.8rem">
                    Format: JPG, PNG, GIF, WEBP &mdash; Maksimal 2 MB (opsional)
                </small>
                <div id="foto-preview" style="margin-top:0.75rem;display:none">
                    <img id="preview-img" src="" alt="Preview"
                        style="max-width:180px;max-height:240px;border-radius:8px;
                               box-shadow:0 2px 8px rgba(0,0,0,.15);object-fit:cover;">
                </div>
            </div>

            <div class="form-group">
                <label for="ulasan">Ulasan / Resensi <span style="color:#c0392b">*</span></label>
                <textarea id="ulasan" name="ulasan"
                    placeholder="Tuliskan ulasan Anda tentang buku ini... (minimal 20 karakter)"
                    required><?= htmlspecialchars($old['ulasan'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="link_tokopedia">Link Pembelian Tokopedia (Opsional)</label>
                <input type="url" id="link_tokopedia" name="link_tokopedia"
                    value="<?= htmlspecialchars($old['link_tokopedia'] ?? '') ?>"
                    placeholder="Contoh: https://tokopedia.com/..." maxlength="500">
            </div>
            
            <div class="form-group">
                <label for="link_shopee">Link Pembelian Shopee (Opsional)</label>
                <input type="url" id="link_shopee" name="link_shopee"
                    value="<?= htmlspecialchars($old['link_shopee'] ?? '') ?>"
                    placeholder="Contoh: https://shopee.co.id/..." maxlength="500">
            </div>

            <div class="form-group">
                <label for="link_gramedia">Link Pembelian Gramedia (Opsional)</label>
                <input type="url" id="link_gramedia" name="link_gramedia"
                    value="<?= htmlspecialchars($old['link_gramedia'] ?? '') ?>"
                    placeholder="Contoh: https://gramedia.com/..." maxlength="500">
            </div>

            <div class="form-group">
                <label>Rating <span style="color:#c0392b">*</span></label>
                <div class="star-select" id="starSelect">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" id="rating<?= $i ?>" name="rating" value="<?= $i ?>"
                        <?= (intval($old['rating'] ?? 0) === $i) ? 'checked' : '' ?>>
                    <label for="rating<?= $i ?>">★</label>
                    <?php endfor; ?>
                </div>
                <small style="color:var(--ink-light);font-size:0.8rem">Klik bintang untuk memberi rating</small>
            </div>

            <div style="display:flex;gap:0.75rem;margin-top:0.5rem">
                <button type="submit" class="btn btn-primary">Simpan Resensi</button>
                <a href="katalog.php" class="btn btn-outline">Batal</a>
            </div>

        </form>
    </div>
</div>

<script>
// Tampilkan / sembunyikan input genre custom
function toggleGenreCustom(val) {
    const wrap = document.getElementById('genre-custom-wrap');
    wrap.style.display = (val === 'Lainnya') ? 'block' : 'none';
    if (val !== 'Lainnya') {
        document.getElementById('genre_custom').value = '';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('genre');
    if (sel) toggleGenreCustom(sel.value);
});

function previewFoto(input) {
    const wrap = document.getElementById('foto-preview');
    const img  = document.getElementById('preview-img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            img.src       = e.target.result;
            wrap.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        wrap.style.display = 'none';
    }
}
</script>

<?php include ('footer.php'); ?>