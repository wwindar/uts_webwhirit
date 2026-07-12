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
$stmt = $conn->prepare("SELECT id, username, nama_lengkap, bio, genre_favorit, foto_profil, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$userResult = $stmt->get_result();
if ($userResult->num_rows === 0) {
    header("Location: katalog.php");
    exit();
}
$user = $userResult->fetch_assoc();
$stmt->close();

// Set judul tab = nama user yang sedang dilihat
$pageFullTitle = ($user['nama_lengkap'] ?: $user['username']) . ' | Resensi Buku';

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

// Hitung pengikut (followers)
$stmtFollowers = $conn->prepare("SELECT COUNT(*) as total FROM pengikut WHERE diikuti_id = ?");
$stmtFollowers->bind_param("i", $id);
$stmtFollowers->execute();
$totalFollowers = $stmtFollowers->get_result()->fetch_assoc()['total'];
$stmtFollowers->close();

// Hitung mengikuti (following)
$stmtFollowing = $conn->prepare("SELECT COUNT(*) as total FROM pengikut WHERE pengikut_id = ?");
$stmtFollowing->bind_param("i", $id);
$stmtFollowing->execute();
$totalFollowing = $stmtFollowing->get_result()->fetch_assoc()['total'];
$stmtFollowing->close();

// Cek apakah saya sudah follow
$isFollowing = false;
$stmtCek = $conn->prepare("SELECT id FROM pengikut WHERE pengikut_id = ? AND diikuti_id = ?");
$stmtCek->bind_param("ii", $_SESSION['user_id'], $id);
$stmtCek->execute();
if ($stmtCek->get_result()->num_rows > 0) {
    $isFollowing = true;
}
$stmtCek->close();

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

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<div class="main-content">
    <div style="margin-bottom:1.5rem">
        <a href="katalog.php" class="btn btn-outline btn-sm">← Kembali ke Katalog</a>
    </div>

    <div class="page-header">
        <h1>Profil Pengguna</h1>
        <p>Melihat profil dan koleksi resensi dari <strong><?= htmlspecialchars($user['username']) ?></strong>.</p>
    </div>

    <div class="profil-grid" style="display:grid;gap:1.5rem;align-items:start">

        <!-- ── Kartu Info Profil ── -->
        <div style="background:var(--paper);border:1px solid var(--border);border-top:3px solid var(--gold);
                    border-radius:4px;padding:1.8rem;box-shadow:0 4px 20px var(--shadow)">

            <div style="text-align:center;margin-bottom:1.5rem">
                <?php if (!empty($user['foto_profil']) && file_exists('uploads/' . $user['foto_profil'])): ?>
                    <img src="uploads/<?= htmlspecialchars($user['foto_profil']) ?>" alt="Foto Profil"
                         style="width:80px;height:80px;border-radius:50%;object-fit:cover;
                                margin:0 auto 0.75rem;border:3px solid var(--gold);box-shadow:0 2px 10px rgba(0,0,0,0.1);display:block">
                <?php else: ?>
                    <div style="width:72px;height:72px;background:var(--ink);border-radius:50%;
                                display:flex;align-items:center;justify-content:center;
                                margin:0 auto 0.75rem;font-size:2rem;border:3px solid var(--gold)">👤</div>
                <?php endif; ?>
                <h2 style="font-family:var(--font-display);font-size:1.3rem;color:var(--ink)">
                    <?= htmlspecialchars($user['nama_lengkap'] ?: $user['username']) ?>
                </h2>
                <div style="font-size:0.9rem;color:var(--ink-light);margin-bottom:0.4rem;font-weight:500;">
                    @<?= htmlspecialchars($user['username']) ?>
                </div>
                <span style="font-size:0.78rem;color:var(--brown);background:rgba(212,168,67,0.12);
                             border:1px solid rgba(212,168,67,0.3);border-radius:20px;padding:0.2rem 0.75rem">
                    Member
                </span>
            </div>

            <div style="border-top:1px solid var(--border);padding-top:1rem">
                <?php if (!empty($user['bio'])): ?>
                <div style="margin-bottom:1.25rem;text-align:center">
                    <p style="font-size:0.9rem;color:var(--ink);line-height:1.5;font-style:italic">
                        "<?= nl2br(htmlspecialchars($user['bio'])) ?>"
                    </p>
                </div>
                <?php endif; ?>
                
                <div style="margin-bottom:0.9rem">
                    <div style="font-size:0.75rem;font-weight:500;color:var(--ink-light);
                                text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem">Nama Tampilan</div>
                    <div style="font-size:0.95rem;color:var(--ink);font-weight:500">
                        <?= htmlspecialchars($user['nama_lengkap'] ?: '-') ?>
                    </div>
                </div>
                <div style="margin-bottom:0.9rem">
                    <div style="font-size:0.75rem;font-weight:500;color:var(--ink-light);
                                text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem">Bergabung Sejak</div>
                    <div style="font-size:0.95rem;color:var(--ink)">
                        <?= date('d F Y', strtotime($user['created_at'])) ?>
                    </div>
                </div>

                <!-- Statistik -->
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;margin-bottom:0.9rem">
                    <a href="#recent-resensi" style="text-decoration:none; color:inherit; display:block">
                        <div style="background:var(--page-bg);border-radius:8px;padding:0.75rem;text-align:center;transition:transform 0.2s;cursor:pointer" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            <div style="font-size:1.5rem;font-family:var(--font-display);color:var(--gold);
                                        font-weight:700;line-height:1"><?= $totalResensi ?></div>
                            <div style="font-size:0.72rem;color:var(--ink-light);margin-top:0.2rem">Resensi</div>
                        </div>
                    </a>
                    <div style="background:var(--page-bg);border-radius:8px;padding:0.75rem;text-align:center;transition:transform 0.2s;cursor:pointer" onclick="openKoneksiModal('pengikut', <?= $id ?>)" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <div style="font-size:1.5rem;font-family:var(--font-display);color:var(--gold);
                                    font-weight:700;line-height:1" id="follower-count"><?= $totalFollowers ?></div>
                        <div style="font-size:0.72rem;color:var(--ink-light);margin-top:0.2rem">Pengikut</div>
                    </div>
                    <div style="background:var(--page-bg);border-radius:8px;padding:0.75rem;text-align:center;transition:transform 0.2s;cursor:pointer" onclick="openKoneksiModal('mengikuti', <?= $id ?>)" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <div style="font-size:1.5rem;font-family:var(--font-display);color:var(--gold);
                                    font-weight:700;line-height:1" id="following-count"><?= $totalFollowing ?></div>
                        <div style="font-size:0.72rem;color:var(--ink-light);margin-top:0.2rem">Mengikuti</div>
                    </div>
                </div>
                <div style="margin-bottom:0.9rem">
                    <div style="font-size:0.75rem;font-weight:500;color:var(--ink-light);
                                text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem">Rata-rata Rating (Resensi)</div>
                    <div style="font-size:0.95rem;color:var(--ink)">
                        <?= $avgRating > 0 ? $avgRating : '-' ?> ★
                    </div>
                </div>
                <div style="margin-bottom:0.9rem">
                    <div style="font-size:0.75rem;font-weight:500;color:var(--ink-light);
                                text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.4rem">Genre Favorit</div>
                    <?php
                    $genreFavArray = array_filter(array_map('trim', explode(',', $user['genre_favorit'] ?? '')));
                    $genreColors = ['#e74c3c','#9b59b6','#2980b9','#27ae60','#e67e22','#1abc9c','#c0392b'];
                    if (!empty($genreFavArray)):
                    ?>
                    <div style="display:flex;flex-wrap:wrap;gap:0.35rem">
                        <?php foreach ($genreFavArray as $idx => $gf): ?>
                        <span style="background:<?= $genreColors[$idx % count($genreColors)] ?>22;
                                     color:<?= $genreColors[$idx % count($genreColors)] ?>;
                                     border:1px solid <?= $genreColors[$idx % count($genreColors)] ?>44;
                                     border-radius:20px;padding:0.2rem 0.75rem;
                                     font-size:0.8rem;font-weight:600">
                            <?= htmlspecialchars($gf) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div style="font-size:0.9rem;color:var(--ink-light);font-style:italic">Belum diatur</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:0.75rem">
                <button id="btn-follow" data-id="<?= $id ?>" class="btn <?= $isFollowing ? 'btn-outline' : 'btn-primary' ?> btn-full">
                    <?= $isFollowing ? 'Berhenti Mengikuti' : 'Ikuti' ?>
                </button>
                <a href="dm.php?user_id=<?= $id ?>" class="btn btn-gold btn-full" style="text-align:center;display:block">
                    💬 Kirim Pesan
                </a>
                <button onclick="bukaModalBagikan()" class="btn btn-outline btn-full">
                    🔗 Bagikan Profil
                </button>
            </div>
        </div>

        <!-- ── Resensi Terbaru User Ini ── -->
        <div id="recent-resensi" style="background:var(--paper);border:1px solid var(--border);border-top:3px solid #3498db;
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

<!-- ══════════════ MODAL KONEKSI (FOLLOWER/FOLLOWING) ══════════════ -->
<div id="modal-koneksi" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;
     overflow-y:auto;padding:2rem 1rem" onclick="if(event.target===this){this.style.display='none';document.body.style.overflow=''}">
    <div style="background:#fff;border-radius:16px;max-width:400px;margin:auto;padding:1.5rem;position:relative">
        <button onclick="document.getElementById('modal-koneksi').style.display='none';document.body.style.overflow=''" style="position:absolute;top:1rem;right:1rem;
            background:none;border:none;font-size:1.4rem;cursor:pointer;color:#888;line-height:1">×</button>
        <h2 id="modal-koneksi-title" style="font-family:var(--font-head);font-size:1.2rem;margin-bottom:1rem;text-align:center">Daftar</h2>
        
        <div id="modal-koneksi-body" style="max-height:60vh;overflow-y:auto;padding-right:0.5rem">
            <div style="text-align:center;color:#888;padding:2rem">Memuat...</div>
        </div>
    </div>
</div>

<!-- ══════════════ MODAL BAGIKAN ══════════════ -->
<div id="modal-bagikan" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;
     overflow-y:auto;padding:2rem 1rem" onclick="if(event.target===this){this.style.display='none';document.body.style.overflow=''}">
    <div style="background:#fff;border-radius:16px;max-width:400px;margin:auto;padding:2rem;position:relative;text-align:center;">
        <button onclick="document.getElementById('modal-bagikan').style.display='none';document.body.style.overflow=''" style="position:absolute;top:1rem;right:1rem;
            background:none;border:none;font-size:1.4rem;cursor:pointer;color:#888;line-height:1">×</button>
        <h2 style="font-family:var(--font-head);font-size:1.3rem;margin-bottom:0.5rem">Bagikan Profil</h2>
        <p style="color:var(--ink-light);font-size:0.85rem;margin-bottom:1.5rem">Arahkan kamera untuk memindai QR Code.</p>
        
        <div id="qrcode" style="display:flex;justify-content:center;margin-bottom:1.5rem;padding:1rem;background:white;border-radius:12px;border:1px solid var(--border);"></div>
        
        <div style="display:flex;gap:0.5rem;align-items:center;">
            <input type="text" id="link-profil" readonly value="" style="flex:1;padding:0.6rem;border:1px solid var(--border);border-radius:6px;font-size:0.85rem;background:#f8f9fa;">
            <button onclick="copyLinkProfil()" class="btn btn-gold btn-sm" style="white-space:nowrap;">Salin</button>
        </div>
    </div>
</div>

<script>
let qrcodeInstance = null;
function bukaModalBagikan() {
    document.getElementById('modal-bagikan').style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Construct public profile link
    const link = window.location.href; // since we are on profil_publik.php
    document.getElementById('link-profil').value = link;
    
    if (!qrcodeInstance) {
        qrcodeInstance = new QRCode(document.getElementById("qrcode"), {
            text: link,
            width: 200,
            height: 200,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    }
}

function copyLinkProfil() {
    const linkInput = document.getElementById('link-profil');
    linkInput.select();
    document.execCommand('copy');
    alert('Link profil berhasil disalin!');
}
document.getElementById('btn-follow').addEventListener('click', function() {
    const btn = this;
    const userId = btn.getAttribute('data-id');
    const isFollowing = btn.classList.contains('btn-outline');
    const action = isFollowing ? 'unfollow' : 'follow';
    
    // Disable btn
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('diikuti_id', userId);
    formData.append('action', action);
    
    fetch('follow_action.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            const countEl = document.getElementById('follower-count');
            let count = parseInt(countEl.innerText);
            if(action === 'follow') {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline');
                btn.innerText = 'Berhenti Mengikuti';
                countEl.innerText = count + 1;
            } else {
                btn.classList.remove('btn-outline');
                btn.classList.add('btn-primary');
                btn.innerText = 'Ikuti';
                countEl.innerText = count - 1;
            }
        } else {
            if (typeof buatAlert === 'function') buatAlert(data.message, 'error');
            else alert(data.message);
        }
    })
    .catch(err => console.error(err))
    .finally(() => {
        btn.disabled = false;
    });
});

// Koneksi Modal Script
function openKoneksiModal(type, userId) {
    const modal = document.getElementById('modal-koneksi');
    const title = document.getElementById('modal-koneksi-title');
    const body = document.getElementById('modal-koneksi-body');
    
    title.innerText = type === 'pengikut' ? 'Pengikut' : 'Mengikuti';
    body.innerHTML = '<div style="text-align:center;color:#888;padding:2rem">Memuat...</div>';
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    fetch(`get_koneksi.php?type=${type}&user_id=${userId}`)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            body.innerHTML = '';
            if (data.data.length === 0) {
                body.innerHTML = '<div style="text-align:center;color:#888;padding:2rem">Belum ada data.</div>';
                return;
            }
            
            data.data.forEach(user => {
                const item = document.createElement('div');
                item.style.display = 'flex';
                item.style.alignItems = 'center';
                item.style.justifyContent = 'space-between';
                item.style.padding = '0.75rem 0';
                item.style.borderBottom = '1px solid var(--border)';
                
                let imgHtml = '';
                if (user.foto_profil) {
                    imgHtml = `<img src="uploads/${user.foto_profil}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:1px solid var(--border)">`;
                } else {
                    imgHtml = `<div style="width:40px;height:40px;border-radius:50%;background:var(--gold);color:white;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:1.1rem">${user.username.charAt(0).toUpperCase()}</div>`;
                }
                
                let btnHtml = '';
                if (!user.is_me) {
                    const btnClass = user.is_following ? 'btn-outline' : 'btn-primary';
                    const btnText = user.is_following ? 'Berhenti' : 'Ikuti';
                    btnHtml = `<button class="btn ${btnClass} btn-sm" onclick="toggleFollowModal(this, ${user.id})">${btnText}</button>`;
                }
                
                item.innerHTML = `
                    <div style="display:flex;align-items:center;gap:0.75rem">
                        ${imgHtml}
                        <a href="profil_publik.php?id=${user.id}" style="font-weight:600;color:var(--ink);text-decoration:none">${user.username}</a>
                    </div>
                    <div>
                        ${btnHtml}
                    </div>
                `;
                body.appendChild(item);
            });
        } else {
            body.innerHTML = `<div style="text-align:center;color:#e74c3c;padding:2rem">${data.message}</div>`;
        }
    })
    .catch(err => {
        body.innerHTML = '<div style="text-align:center;color:#e74c3c;padding:2rem">Gagal memuat data.</div>';
        console.error(err);
    });
}

function toggleFollowModal(btn, userId) {
    const isFollowing = btn.classList.contains('btn-outline');
    const action = isFollowing ? 'unfollow' : 'follow';
    
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('diikuti_id', userId);
    formData.append('action', action);
    
    fetch('follow_action.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            if(action === 'follow') {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline');
                btn.innerText = 'Berhenti';
            } else {
                btn.classList.remove('btn-outline');
                btn.classList.add('btn-primary');
                btn.innerText = 'Ikuti';
            }
        } else {
            alert(data.message);
        }
    })
    .catch(err => console.error(err))
    .finally(() => {
        btn.disabled = false;
    });
}
</script>

<?php include ('footer.php'); ?>
