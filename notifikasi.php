<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$pageTitle = 'Notifikasi';
$userId    = $_SESSION['user_id'];

// Tandai semua sudah dibaca
if (isset($_GET['baca_semua'])) {
    $conn->query("UPDATE notifikasi SET sudah_dibaca = 1 WHERE user_id = $userId");
    header("Location: notifikasi.php");
    exit();
}

// Tandai satu sudah dibaca
if (isset($_GET['baca']) && is_numeric($_GET['baca'])) {
    $nid = intval($_GET['baca']);
    $st  = $conn->prepare("UPDATE notifikasi SET sudah_dibaca = 1 WHERE id = ? AND user_id = ?");
    $st->bind_param("ii", $nid, $userId);
    $st->execute();
    $st->close();
    // redirect ke detail resensi jika ada resensi_id
    $r2 = $conn->prepare("SELECT resensi_id FROM notifikasi WHERE id = ? AND user_id = ?");
    $r2->bind_param("ii", $nid, $userId);
    $r2->execute();
    $rr = $r2->get_result()->fetch_assoc();
    $r2->close();
    if (!empty($rr['resensi_id'])) {
        header("Location: detail.php?id=" . $rr['resensi_id']);
    } else {
        header("Location: notifikasi.php");
    }
    exit();
}

// Hapus satu notifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $nid = intval($_POST['hapus_id']);
    $st  = $conn->prepare("DELETE FROM notifikasi WHERE id = ? AND user_id = ?");
    $st->bind_param("ii", $nid, $userId);
    $st->execute();
    $st->close();
    header("Location: notifikasi.php");
    exit();
}

$result = $conn->query(
    "SELECT *, created_at AS tgl_kirim FROM notifikasi WHERE user_id = $userId ORDER BY created_at DESC LIMIT 50"
);

$belumBaca = $conn->query(
    "SELECT COUNT(*) as c FROM notifikasi WHERE user_id = $userId AND sudah_dibaca = 0"
)->fetch_assoc()['c'];
?>
<?php include ('header.php'); ?>

<div class="main-content">
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem">
        <div>
            <h1>🔔 Notifikasi</h1>
            <p>
                <?php if ($belumBaca > 0): ?>
                    Kamu punya <strong><?= $belumBaca ?></strong> notifikasi belum dibaca.
                <?php else: ?>
                    Semua notifikasi sudah dibaca.
                <?php endif; ?>
            </p>
        </div>
        <?php if ($belumBaca > 0): ?>
        <a href="notifikasi.php?baca_semua=1" class="btn btn-outline btn-sm">Tandai semua dibaca</a>
        <?php endif; ?>
    </div>

    <?php if ($result->num_rows === 0): ?>
    <div class="empty-state">
        <div class="empty-icon">🔔</div>
        <h3>Belum ada notifikasi</h3>
        <p>Notifikasi akan muncul saat ada pengguna lain yang mulai mengikuti akunmu.</p>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:0.75rem">
        <?php while ($n = $result->fetch_assoc()): ?>
        <div class="form-card" style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;
             padding:0.85rem 1.1rem;
             <?= !$n['sudah_dibaca'] ? 'border-left:4px solid var(--gold);background:rgba(212,175,55,0.06)' : 'opacity:0.75' ?>">
            <div style="flex:1;min-width:0">
                <?php if (!$n['sudah_dibaca']): ?>
                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;
                    background:var(--gold);margin-right:6px;vertical-align:middle"></span>
                <?php endif; ?>
                <span style="font-size:0.95rem">
                    <?= htmlspecialchars($n['pesan']) ?>
                </span>
                <div style="font-size:0.75rem;color:var(--ink-light);margin-top:0.3rem">
                    <?= date('d M Y, H:i', strtotime($n['tgl_kirim'])) ?> WIB
                </div>
            </div>
            <div style="display:flex;gap:0.4rem;align-items:center;flex-shrink:0">
                <?php if (!$n['sudah_dibaca'] && $n['resensi_id']): ?>
                <a href="notifikasi.php?baca=<?= $n['id'] ?>" class="btn btn-outline btn-sm">Lihat</a>
                <?php elseif (!$n['sudah_dibaca']): ?>
                <a href="notifikasi.php?baca=<?= $n['id'] ?>" class="btn btn-outline btn-sm">✓ Baca</a>
                <?php endif; ?>
                <form method="POST" style="margin:0">
                    <input type="hidden" name="hapus_id" value="<?= $n['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus notifikasi">×</button>
                </form>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<?php include ('footer.php'); ?>