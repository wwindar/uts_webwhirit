<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$pageTitle = 'Edit Resensi';
$basePath   = '../';

$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
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

$errors = [];

$genreOptions = [
    'Fiksi Ilmiah', 'Horor', 'Bromance', 'Fiksi Remaja', 'Supranatural',
    'Omegaverse', 'Romantic Comedy', 'Angst', 'Biografi', 'Filsafat',
    'Hurt/Comfort', 'Local/Lokal AU', 'Family', 'Friendship', 'Novel',
    'Puisi', 'Cerpen', 'Sejarah', 'Romance', 'Thriller', 'Mystery',
    'Fantasy', 'Science Fiction', 'Slice of Life', 'Young Adult', 'Adult',
    'Childrens Literature', 'Urban Fantasy', 'Historical', 'Dystopian',
    'Contemporary', 'Adventure', 'Thriller & Mystery', 'Lainnya'
];

// Tentukan apakah genre yang tersimpan ada di daftar atau custom
$savedGenre      = $buku['genre'] ?? '';
$isGenreInList   = in_array($savedGenre, $genreOptions) || $savedGenre === '';
$genreSelectVal  = $isGenreInList ? $savedGenre : 'Lainnya';
$genreCustomVal  = $isGenreInList ? '' : $savedGenre;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // Perbarui nilai untuk re-render form jika ada error
    $genreSelectVal = $genreSelect;
    $genreCustomVal = $genreCustom;

    if (empty($judul))              $errors[] = 'Judul buku wajib diisi.';
    elseif (strlen($judul) > 255)   $errors[] = 'Judul buku maksimal 255 karakter.';

    if (empty($penulis))            $errors[] = 'Nama penulis wajib diisi.';

    if (empty($ulasan))             $errors[] = 'Ulasan wajib diisi.';
    elseif (strlen($ulasan) < 20)   $errors[] = 'Ulasan minimal 20 karakter.';

    if ($rating < 1 || $rating > 5) $errors[] = 'Rating wajib dipilih (1-5 bintang).';

    // Handle upload foto baru
    $fotoNama = $buku['foto']; // default: foto lama
    if (!empty($_FILES['foto']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize      = 2 * 1024 * 1024; // 2 MB

        if (!in_array($_FILES['foto']['type'], $allowedTypes)) {
            $errors[] = 'Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.';
        } elseif ($_FILES['foto']['size'] > $maxSize) {
            $errors[] = 'Ukuran foto maksimal 2 MB.';
        } else {
            $ext      = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $newFoto  = 'foto_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $newFoto)) {
                // Hapus foto lama jika ada
                if ($fotoNama && file_exists($uploadDir . $fotoNama)) {
                    @unlink($uploadDir . $fotoNama);
                }
                $fotoNama = $newFoto;
            } else {
                $errors[] = 'Gagal mengunggah foto. Silakan coba lagi.';
            }
        }
    }

    // Opsi hapus foto yang ada
    if (isset($_POST['hapus_foto']) && $_POST['hapus_foto'] === '1') {
        $uploadDir = 'uploads/';
        if ($buku['foto'] && file_exists($uploadDir . $buku['foto'])) {
            @unlink($uploadDir . $buku['foto']);
        }
        $fotoNama = null;
    }

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "UPDATE resensi SET judul_buku=?, penulis=?, genre=?, ulasan=?, rating=?, foto=?, link_tokopedia=?, link_shopee=?, link_gramedia=? WHERE id=?"
        );
        $stmt->bind_param("ssssisisss", $judul, $penulis, $genre, $ulasan, $rating, $fotoNama, $link_tokopedia, $link_shopee, $link_gramedia, $id);

        if ($stmt->execute()) {
            $_SESSION['flash']      = 'Resensi "' . $judul . '" berhasil diperbarui!';
            $_SESSION['flash_type'] = 'success';
            header("Location: katalog.php");
            exit();
        } else {
            $errors[] = 'Gagal memperbarui data. Silakan coba lagi.';
        }
        $stmt->close();
    }

    $buku['judul_buku'] = $judul;
    $buku['penulis']    = $penulis;
    $buku['genre']      = $genre;
    $buku['ulasan']     = $ulasan;
    $buku['rating']     = $rating;
    $buku['foto']       = $fotoNama;
    $buku['link_tokopedia'] = $link_tokopedia;
    $buku['link_shopee']    = $link_shopee;
    $buku['link_gramedia']  = $link_gramedia;
}
?>
<?php include ('header.php'); ?>

<div class="main-content">
    <div class="page-header">
        <h1>Edit Resensi</h1>
        <p>Perbarui informasi resensi buku.</p>
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
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="judul_buku">Judul Buku <span style="color:#c0392b">*</span></label>
                    <input type="text" id="judul_buku" name="judul_buku"
                        value="<?= htmlspecialchars($buku['judul_buku']) ?>"
                        required maxlength="255">
                </div>
                <div class="form-group">
                    <label for="penulis">Penulis <span style="color:#c0392b">*</span></label>
                    <input type="text" id="penulis" name="penulis"
                        value="<?= htmlspecialchars($buku['penulis']) ?>"
                        required maxlength="100">
                </div>
            </div>

            <!-- GENRE -->
            <div class="form-group">
                <label for="genre">Genre</label>
                <select id="genre" name="genre" onchange="toggleGenreCustom(this.value)">
                    <option value="">— Pilih Genre —</option>
                    <?php foreach ($genreOptions as $g): ?>
                    <option value="<?= htmlspecialchars($g) ?>"
                        <?= $genreSelectVal === $g ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Input genre custom -->
            <div class="form-group" id="genre-custom-wrap"
                style="display:<?= $genreSelectVal === 'Lainnya' ? 'block' : 'none' ?>;margin-top:-0.5rem">
                <label for="genre_custom">Tulis genre sendiri</label>
                <input type="text" id="genre_custom" name="genre_custom"
                    value="<?= htmlspecialchars($genreCustomVal) ?>"
                    placeholder="Contoh: Romance, Thriller / Angst, Hurt/Comfort"
                    maxlength="100">
                <small style="color:var(--ink-light);font-size:0.8rem">
                    Bisa tulis lebih dari satu genre, pisahkan dengan koma atau garis miring.
                </small>
            </div>

            <!-- FOTO -->
            <div class="form-group">
                <label>Foto Sampul / Cover Buku</label>

                <?php if (!empty($buku['foto']) && file_exists('uploads/' . $buku['foto'])): ?>
                <div style="margin-bottom:0.75rem">
                    <p style="font-size:0.85rem;color:var(--ink-light);margin-bottom:0.4rem">Foto saat ini:</p>
                    <img src="uploads/<?= htmlspecialchars($buku['foto']) ?>" alt="Foto resensi"
                        style="max-width:150px;max-height:200px;border-radius:8px;
                               box-shadow:0 2px 8px rgba(0,0,0,.15);object-fit:cover;display:block;margin-bottom:0.5rem">
                    <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.85rem;cursor:pointer">
                        <input type="checkbox" name="hapus_foto" value="1" id="hapus_foto"
                            onchange="toggleHapusFoto(this)">
                        Hapus foto ini
                    </label>
                </div>
                <?php endif; ?>

                <label for="foto" style="font-size:0.9rem;margin-bottom:0.3rem;display:block">
                    <?= !empty($buku['foto']) ? 'Ganti dengan foto baru (opsional):' : 'Unggah foto (opsional):' ?>
                </label>
                <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/gif,image/webp"
                    onchange="previewFoto(this)">
                <small style="color:var(--ink-light);font-size:0.8rem">
                    Format: JPG, PNG, GIF, WEBP &mdash; Maksimal 2 MB
                </small>
                <div id="foto-preview" style="margin-top:0.75rem;display:none">
                    <img id="preview-img" src="" alt="Preview"
                        style="max-width:180px;max-height:240px;border-radius:8px;
                               box-shadow:0 2px 8px rgba(0,0,0,.15);object-fit:cover;">
                </div>
            </div>

            <div class="form-group">
                <label for="ulasan">Ulasan / Resensi <span style="color:#c0392b">*</span></label>
                <textarea id="ulasan" name="ulasan" required><?= htmlspecialchars($buku['ulasan']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="link_tokopedia">Link Pembelian Tokopedia (Opsional)</label>
                <input type="url" id="link_tokopedia" name="link_tokopedia"
                    value="<?= htmlspecialchars($buku['link_tokopedia'] ?? '') ?>"
                    placeholder="Contoh: https://tokopedia.com/..." maxlength="500">
            </div>

            <div class="form-group">
                <label for="link_shopee">Link Pembelian Shopee (Opsional)</label>
                <input type="url" id="link_shopee" name="link_shopee"
                    value="<?= htmlspecialchars($buku['link_shopee'] ?? '') ?>"
                    placeholder="Contoh: https://shopee.co.id/..." maxlength="500">
            </div>

            <div class="form-group">
                <label for="link_gramedia">Link Pembelian Gramedia (Opsional)</label>
                <input type="url" id="link_gramedia" name="link_gramedia"
                    value="<?= htmlspecialchars($buku['link_gramedia'] ?? '') ?>"
                    placeholder="Contoh: https://gramedia.com/..." maxlength="500">
            </div>

            <div class="form-group">
                <label>Rating <span style="color:#c0392b">*</span></label>
                <div class="star-select">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" id="rating<?= $i ?>" name="rating" value="<?= $i ?>"
                        <?= intval($buku['rating']) === $i ? 'checked' : '' ?>>
                    <label for="rating<?= $i ?>">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <div style="display:flex;gap:0.75rem;margin-top:0.5rem">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="detail.php?id=<?= $id ?>" class="btn btn-outline">Batal</a>
            </div>

        </form>
    </div>
</div>

<script>
function toggleGenreCustom(val) {
    const wrap = document.getElementById('genre-custom-wrap');
    wrap.style.display = (val === 'Lainnya') ? 'block' : 'none';
    if (val !== 'Lainnya') {
        document.getElementById('genre_custom').value = '';
    }
}

function toggleHapusFoto(cb) {
    const fotoInput = document.getElementById('foto');
    const preview   = document.getElementById('foto-preview');
    if (cb.checked) {
        fotoInput.value   = '';
        preview.style.display = 'none';
        fotoInput.disabled    = true;
    } else {
        fotoInput.disabled = false;
    }
}

function previewFoto(input) {
    const wrap = document.getElementById('foto-preview');
    const img  = document.getElementById('preview-img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            img.src            = e.target.result;
            wrap.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        wrap.style.display = 'none';
    }
}
</script>

<?php include ('footer.php'); ?>