<?php
session_start();
require_once ('db.php');
require_once ('auth.php');

requireLogin();

$pageTitle = 'Katalog';

// ─── AJAX handler untuk modal CRUD ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];

    if ($action === 'tambah') {
        $judul   = trim($_POST['judul_buku'] ?? '');
        $penulis = trim($_POST['penulis']    ?? '');
        $genre   = trim($_POST['genre']      ?? '');
        $ulasan  = trim($_POST['ulasan']     ?? '');
        $rating  = intval($_POST['rating']   ?? 0);
        $userId  = $_SESSION['user_id'];
        $errors  = [];
        if (!$judul)  $errors[] = 'Judul buku wajib diisi.';
        if (!$penulis) $errors[] = 'Penulis wajib diisi.';
        if (!$ulasan || strlen($ulasan) < 20) $errors[] = 'Ulasan minimal 20 karakter.';
        if ($rating < 1 || $rating > 5) $errors[] = 'Rating wajib dipilih.';
        if ($errors) { echo json_encode(['success'=>false,'message'=>implode('<br>',$errors)]); exit(); }
        $st = $conn->prepare("INSERT INTO resensi (judul_buku,penulis,genre,ulasan,rating,user_id) VALUES (?,?,?,?,?,?)");
        $st->bind_param("ssssii",$judul,$penulis,$genre,$ulasan,$rating,$userId);
        echo $st->execute()
            ? json_encode(['success'=>true,'message'=>"Resensi \"$judul\" berhasil ditambahkan!"])
            : json_encode(['success'=>false,'message'=>'Gagal menyimpan data.']);
        $st->close(); exit();
    }

    if ($action === 'get') {
        $id = intval($_POST['id'] ?? 0);
        $st = $conn->prepare("SELECT * FROM resensi WHERE id=?");
        $st->bind_param("i",$id); $st->execute();
        echo json_encode($st->get_result()->fetch_assoc() ?? []);
        $st->close(); exit();
    }

    if ($action === 'edit') {
        $id      = intval($_POST['id']       ?? 0);
        $judul   = trim($_POST['judul_buku'] ?? '');
        $penulis = trim($_POST['penulis']    ?? '');
        $genre   = trim($_POST['genre']      ?? '');
        $ulasan  = trim($_POST['ulasan']     ?? '');
        $rating  = intval($_POST['rating']   ?? 0);
        $errors  = [];
        if (!$judul)  $errors[] = 'Judul buku wajib diisi.';
        if (!$penulis) $errors[] = 'Penulis wajib diisi.';
        if (!$ulasan || strlen($ulasan) < 20) $errors[] = 'Ulasan minimal 20 karakter.';
        if ($rating < 1 || $rating > 5) $errors[] = 'Rating wajib dipilih.';
        if ($errors) { echo json_encode(['success'=>false,'message'=>implode('<br>',$errors)]); exit(); }
        $st = $conn->prepare("UPDATE resensi SET judul_buku=?,penulis=?,genre=?,ulasan=?,rating=? WHERE id=?");
        $st->bind_param("ssssii",$judul,$penulis,$genre,$ulasan,$rating,$id);
        echo $st->execute()
            ? json_encode(['success'=>true,'message'=>"Resensi \"$judul\" berhasil diperbarui!"])
            : json_encode(['success'=>false,'message'=>'Gagal memperbarui data.']);
        $st->close(); exit();
    }

    if ($action === 'hapus') {
        $id = intval($_POST['id'] ?? 0);
        $st = $conn->prepare("DELETE FROM resensi WHERE id=?");
        $st->bind_param("i",$id);
        echo $st->execute()
            ? json_encode(['success'=>true,'message'=>'Resensi berhasil dihapus.'])
            : json_encode(['success'=>false,'message'=>'Gagal menghapus data.']);
        $st->close(); exit();
    }
}

// ─── Query katalog ────────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$genre  = trim($_GET['genre']  ?? '');
$sort   = $_GET['sort'] ?? 'terbaru';
$where=[]; $params=[]; $types='';
if ($search!=='') { $where[]="(judul_buku LIKE ? OR penulis LIKE ?)"; $like="%$search%"; $params[]=$like; $params[]=$like; $types.='ss'; }
if ($genre!=='')  { $where[]="genre = ?"; $params[]=$genre; $types.='s'; }
$wc = $where ? 'WHERE '.implode(' AND ',$where) : '';
$oc = match($sort) { 'judul'=>'ORDER BY judul_buku ASC','rating_tinggi'=>'ORDER BY rating DESC','rating_rendah'=>'ORDER BY rating ASC',default=>'ORDER BY tgl_input DESC' };
$sql  = "SELECT * FROM resensi $wc $oc";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types,...$params);
$stmt->execute(); $result = $stmt->get_result();
$genreResult = $conn->query("SELECT DISTINCT genre FROM resensi WHERE genre IS NOT NULL AND genre != '' ORDER BY genre");
$flashMsg  = $_SESSION['flash']      ?? '';
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash'], $_SESSION['flash_type']);
$genreOptions = ['Fiksi Ilmiah','Horor','Bromance','Fiksi Remaja','Supranatural','Omegaverse','Romantic Comedy','Angst','Biografi','Filsafat','Hurt/Comfort','Local/Lokal AU','Family','Friendship','Novel','Puisi','Cerpen','Sejarah','Romance','Thriller','Mystery','Fantasy','Science Fiction','Slice of Life','Young Adult','Adult','Childrens Literature','Urban Fantasy','Historical','Dystopian','Contemporary','Adventure','Thriller & Mystery','Lainnya'];
function renderStars($r){$s='';for($i=1;$i<=5;$i++)$s.=$i<=$r?'★':'☆';return $s;}
?>
<?php include ('header.php'); ?>

<link  href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link  href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"      rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(26,86,50,.35);z-index:1000;align-items:center;justify-content:center;padding:1rem}
.modal-overlay.show{display:flex}
.modal-box{background:#fff;border-radius:16px;width:100%;max-width:620px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(26,86,50,.25);animation:modalIn .25s ease}
@keyframes modalIn{from{opacity:0;transform:scale(.95) translateY(-10px)}to{opacity:1;transform:none}}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid var(--border)}
.modal-header h3{font-family:var(--font-display);font-size:1.2rem;color:var(--ink);margin:0}
.modal-close{background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--ink-light);line-height:1;padding:.2rem .4rem;border-radius:6px}
.modal-close:hover{background:var(--rose-light)}
.modal-body{padding:1.5rem}
.modal-footer{padding:1rem 1.5rem;border-top:1px solid var(--border);display:flex;gap:.75rem;justify-content:flex-end}
.select2-container--default .select2-selection--single{height:42px;border:1px solid var(--border);border-radius:10px;display:flex;align-items:center;padding:0 .9rem;font-family:var(--font-body);font-size:.95rem}
.select2-container--default .select2-selection--single .select2-selection__rendered{color:var(--ink);padding:0;line-height:42px}
.select2-container--default .select2-selection--single .select2-selection__arrow{height:40px;right:8px}
.select2-container--default.select2-container--open .select2-selection--single{border-color:var(--rose-dark);box-shadow:0 0 0 3px rgba(255,215,223,.3)}
.select2-dropdown{border:1px solid var(--border);border-radius:10px;box-shadow:0 8px 24px var(--shadow);font-family:var(--font-body)}
.select2-container--default .select2-results__option--highlighted{background:var(--sage);color:#fff}
.select2-search--dropdown .select2-search__field{border:1px solid var(--border);border-radius:8px;padding:.4rem .75rem;font-family:var(--font-body)}
.select2-container{width:100%!important}
.star-select-modal{display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:.3rem}
.star-select-modal input[type=radio]{display:none}
.star-select-modal label{font-size:1.8rem;cursor:pointer;color:#ccc;transition:color .15s}
.star-select-modal input[type=radio]:checked~label,.star-select-modal label:hover,.star-select-modal label:hover~label{color:var(--rose-dark)}
</style>

<div class="main-content">
  <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem">
    <div><h1>Katalog Resensi</h1><p>Seluruh koleksi ulasan buku yang telah ditambahkan.</p></div>
    <button class="btn btn-gold" onclick="bukaModalTambah()">+ Tambah Resensi</button>
  </div>

  <form method="GET" action="">
    <div class="filter-bar">
      <input type="text" name="search" placeholder="🔍 Cari judul atau penulis..." value="<?= htmlspecialchars($search) ?>">
      <select name="genre" class="select2-filter">
        <option value="">Semua Genre</option>
        <?php while ($g=$genreResult->fetch_assoc()): ?>
        <option value="<?= htmlspecialchars($g['genre']) ?>" <?= $genre===$g['genre']?'selected':'' ?>><?= htmlspecialchars($g['genre']) ?></option>
        <?php endwhile; ?>
      </select>
      <select name="sort">
        <option value="terbaru" <?= $sort==='terbaru'?'selected':'' ?>>Terbaru</option>
        <option value="judul" <?= $sort==='judul'?'selected':'' ?>>A–Z Judul</option>
        <option value="rating_tinggi" <?= $sort==='rating_tinggi'?'selected':'' ?>>Rating Tertinggi</option>
        <option value="rating_rendah" <?= $sort==='rating_rendah'?'selected':'' ?>>Rating Terendah</option>
      </select>
      <button type="submit" class="btn btn-primary">Filter</button>
      <?php if ($search||$genre): ?><a href="katalog.php" class="btn btn-outline">Reset</a><?php endif; ?>
    </div>
  </form>

  <?php if ($result->num_rows===0): ?>
  <div class="empty-state">
    <div class="empty-icon">🔍</div>
    <h3>Tidak ada resensi ditemukan</h3>
    <p><?= $search||$genre?'Coba kata kunci atau filter lain.':'Jadilah yang pertama menambahkan resensi!' ?></p>
    <button class="btn btn-gold" onclick="bukaModalTambah()" style="margin-top:1rem">+ Tambah Resensi</button>
  </div>
  <?php else: ?>
  <p style="color:var(--ink-light);font-size:.85rem;margin-bottom:1rem">Menampilkan <strong><?= $result->num_rows ?></strong> resensi</p>
  <div class="books-grid" id="booksGrid">
    <?php while ($row=$result->fetch_assoc()): ?>
    <div class="book-card" id="card-<?= $row['id'] ?>">
      <div class="book-card-spine"></div>
      <div class="book-card-body">
        <?php if ($row['genre']): ?><span class="book-genre-badge"><?= htmlspecialchars($row['genre']) ?></span><?php endif; ?>
        <div class="book-title"><?= htmlspecialchars($row['judul_buku']) ?></div>
        <div class="book-author">oleh <span><?= htmlspecialchars($row['penulis']) ?></span></div>
        <div class="book-ulasan"><?= htmlspecialchars($row['ulasan']) ?></div>
        <div class="book-meta">
          <span class="stars"><?= renderStars($row['rating']) ?></span>
          <span class="book-date"><?= date('d M Y',strtotime($row['tgl_input'])) ?></span>
        </div>
        <div class="book-actions">
          <a href="detail.php?id=<?= $row['id'] ?>" class="btn btn-outline btn-sm">Detail</a>
          <button class="btn btn-gold btn-sm" onclick="bukaModalEdit(<?= $row['id'] ?>)">Edit</button>
          <button class="btn btn-danger btn-sm" onclick="konfirmasiHapus(<?= $row['id'] ?>,'<?= addslashes(htmlspecialchars($row['judul_buku'])) ?>')">Hapus</button>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
  <?php endif; ?>
</div>

<!-- MODAL TAMBAH -->
<div class="modal-overlay" id="modalTambah">
  <div class="modal-box">
    <div class="modal-header">
      <h3>📝 Tambah Resensi</h3>
      <button class="modal-close" onclick="tutupModal('modalTambah')">✕</button>
    </div>
    <div class="modal-body">
      <form id="formTambah">
        <div class="form-row">
          <div class="form-group">
            <label>Judul Buku <span style="color:red">*</span></label>
            <input type="text" name="judul_buku" placeholder="Contoh: Not The Best, But Still Good" required maxlength="255">
          </div>
          <div class="form-group">
            <label>Penulis <span style="color:red">*</span></label>
            <input type="text" name="penulis" placeholder="Contoh: peachhplease" required maxlength="100">
          </div>
        </div>
        <div class="form-group">
          <label>Genre</label>
          <select name="genre" class="select2-modal">
            <option value="">— Pilih Genre —</option>
            <?php foreach ($genreOptions as $g): ?><option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Ulasan / Resensi <span style="color:red">*</span></label>
          <textarea name="ulasan" rows="5" placeholder="Tuliskan ulasan... (minimal 20 karakter)"></textarea>
        </div>
        <div class="form-group">
          <label>Rating <span style="color:red">*</span></label>
          <div class="star-select-modal">
            <?php for($i=5;$i>=1;$i--): ?><input type="radio" id="tr<?=$i?>" name="rating" value="<?=$i?>"><label for="tr<?=$i?>">★</label><?php endfor; ?>
          </div>
          <small style="color:var(--ink-light);font-size:.8rem">Klik bintang untuk memberi rating</small>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="tutupModal('modalTambah')">Batal</button>
      <button class="btn btn-primary" onclick="submitTambah()">💾 Simpan Resensi</button>
    </div>
  </div>
</div>

<!-- MODAL EDIT -->
<div class="modal-overlay" id="modalEdit">
  <div class="modal-box">
    <div class="modal-header">
      <h3>✏️ Edit Resensi</h3>
      <button class="modal-close" onclick="tutupModal('modalEdit')">✕</button>
    </div>
    <div class="modal-body">
      <form id="formEdit">
        <input type="hidden" name="id" id="e_id">
        <div class="form-row">
          <div class="form-group">
            <label>Judul Buku <span style="color:red">*</span></label>
            <input type="text" name="judul_buku" id="e_judul" required maxlength="255">
          </div>
          <div class="form-group">
            <label>Penulis <span style="color:red">*</span></label>
            <input type="text" name="penulis" id="e_penulis" required maxlength="100">
          </div>
        </div>
        <div class="form-group">
          <label>Genre</label>
          <select name="genre" id="e_genre" class="select2-modal">
            <option value="">— Pilih Genre —</option>
            <?php foreach ($genreOptions as $g): ?><option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Ulasan / Resensi <span style="color:red">*</span></label>
          <textarea name="ulasan" id="e_ulasan" rows="5"></textarea>
        </div>
        <div class="form-group">
          <label>Rating <span style="color:red">*</span></label>
          <div class="star-select-modal" id="editStars">
            <?php for($i=5;$i>=1;$i--): ?><input type="radio" id="er<?=$i?>" name="rating" value="<?=$i?>"><label for="er<?=$i?>">★</label><?php endfor; ?>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="tutupModal('modalEdit')">Batal</button>
      <button class="btn btn-primary" onclick="submitEdit()">💾 Simpan Perubahan</button>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  $('.select2-filter').select2({placeholder:'Semua Genre',allowClear:true});
  $('.modal-overlay').on('click',function(e){if($(e.target).hasClass('modal-overlay'))tutupModal(this.id);});
});

function initSelect2Modal(){
  $('.select2-modal').each(function(){
    if(!$(this).data('select2')){
      $(this).select2({placeholder:'— Pilih Genre —',allowClear:true,dropdownParent:$(this).closest('.modal-overlay'),width:'100%'});
    }
  });
}

function bukaModal(id){$('#'+id).addClass('show');setTimeout(initSelect2Modal,50);}
function tutupModal(id){$('#'+id).removeClass('show');}

function bukaModalTambah(){$('#formTambah')[0].reset();$('.select2-modal','#formTambah').val(null).trigger('change');bukaModal('modalTambah');}

function submitTambah(){
  const fd=new FormData($('#formTambah')[0]);fd.append('ajax_action','tambah');
  $.ajax({url:'katalog.php',method:'POST',data:fd,processData:false,contentType:false,success:function(r){
    if(r.success){tutupModal('modalTambah');Swal.fire({icon:'success',title:'Berhasil! 🎉',html:r.message,confirmButtonColor:'#1a5632'}).then(()=>location.reload());}
    else Swal.fire({icon:'error',title:'Oops!',html:r.message,confirmButtonColor:'#f4a0b5'});
  }});
}

function bukaModalEdit(id){
  Swal.fire({title:'Memuat data...',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
  $.ajax({url:'katalog.php',method:'POST',data:{ajax_action:'get',id:id},success:function(r){
    Swal.close();
    if(!r||!r.id){Swal.fire({icon:'error',title:'Data tidak ditemukan'});return;}
    $('#e_id').val(r.id);$('#e_judul').val(r.judul_buku);$('#e_penulis').val(r.penulis);$('#e_ulasan').val(r.ulasan);
    bukaModal('modalEdit');
    setTimeout(function(){
      $('#e_genre').val(r.genre).trigger('change');
      $('#editStars input[value="'+r.rating+'"]').prop('checked',true);
    },100);
  }});
}

function submitEdit(){
  const fd=new FormData($('#formEdit')[0]);fd.append('ajax_action','edit');
  $.ajax({url:'katalog.php',method:'POST',data:fd,processData:false,contentType:false,success:function(r){
    if(r.success){tutupModal('modalEdit');Swal.fire({icon:'success',title:'Berhasil diperbarui! ✅',html:r.message,confirmButtonColor:'#1a5632'}).then(()=>location.reload());}
    else Swal.fire({icon:'error',title:'Oops!',html:r.message,confirmButtonColor:'#f4a0b5'});
  }});
}

function konfirmasiHapus(id,judul){
  Swal.fire({
    title:'Hapus Resensi?',
    html:'Kamu yakin ingin menghapus resensi<br><strong>"'+judul+'"</strong>?<br><small style="color:#888">Tindakan ini tidak bisa dibatalkan.</small>',
    icon:'warning',showCancelButton:true,
    confirmButtonColor:'#c0392b',cancelButtonColor:'#1a5632',
    confirmButtonText:'🗑️ Ya, Hapus!',cancelButtonText:'Batal',reverseButtons:true
  }).then(function(res){
    if(res.isConfirmed){
      Swal.fire({title:'Menghapus...',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
      $.ajax({url:'katalog.php',method:'POST',data:{ajax_action:'hapus',id:id},success:function(r){
        if(r.success){Swal.fire({icon:'success',title:'Terhapus!',text:r.message,timer:1800,showConfirmButton:false}).then(()=>$('#card-'+id).fadeOut(300,function(){$(this).remove();}));}
        else Swal.fire({icon:'error',title:'Gagal!',text:r.message});
      }});
    }
  });
}
<?php if($flashMsg): ?>
Swal.fire({icon:'<?= $flashType==="success"?"success":"error" ?>',title:'<?= $flashType==="success"?"Berhasil! 🎉":"Gagal!" ?>',text:<?= json_encode($flashMsg) ?>,confirmButtonColor:'#1a5632',timer:3000,timerProgressBar:true});
<?php endif; ?>
</script>

<?php include ('footer.php'); ?>