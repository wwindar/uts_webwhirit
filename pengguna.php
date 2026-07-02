<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$pageTitle = 'Cari Pengguna';
$basePath = '../';
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT u.id, u.username, u.created_at, COUNT(r.id) as total_resensi
    FROM users u
    LEFT JOIN resensi r ON u.id = r.user_id
";

$params = [];
$types = '';

if ($search !== '') {
    $sql .= " WHERE u.username LIKE ?";
    $like = "%$search%";
    $params[] = $like;
    $types .= 's';
}

$sql .= " GROUP BY u.id, u.username, u.created_at ORDER BY u.username ASC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
?>
<?php include ('header.php'); ?>

<div class="main-content">
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem">
        <div>
            <h1>Cari Pengguna</h1>
            <p>Temukan akun pengguna lain dan lihat koleksi resensinya.</p>
        </div>
    </div>

    <form method="GET" action="pengguna.php">
        <div class="filter-bar">
            <input type="text" name="search" placeholder="🔍 Cari username..."
                value="<?= htmlspecialchars($search) ?>" style="flex:1;">
            <button type="submit" class="btn btn-primary">Cari Akun</button>
            <?php if ($search): ?>
            <a href="pengguna.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (count($users) === 0): ?>
    <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <h3>Tidak ada pengguna ditemukan</h3>
        <p><?= $search ? 'Coba kata kunci yang berbeda.' : 'Belum ada pengguna terdaftar.' ?></p>
    </div>
    <?php else: ?>
    <p style="color:var(--ink-light);font-size:0.85rem;margin-bottom:1rem">
        Ditemukan <strong><?= count($users) ?></strong> akun pengguna
    </p>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem;">
        <?php foreach ($users as $u): ?>
        <div style="background:var(--paper);border:1px solid var(--border);border-radius:8px;padding:1.5rem;box-shadow:0 2px 10px var(--shadow);text-align:center;transition:transform 0.2s">
            <div style="width:64px;height:64px;background:var(--ink);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.8rem;border:2px solid var(--gold)">👤</div>
            <h3 style="font-family:var(--font-head);font-size:1.15rem;margin-bottom:0.25rem;color:var(--ink)">
                <?= htmlspecialchars($u['username']) ?>
            </h3>
            <div style="font-size:0.85rem;color:var(--ink-light);margin-bottom:0.75rem">
                Bergabung: <?= date('M Y', strtotime($u['created_at'])) ?>
            </div>
            <div style="background:var(--page-bg);border-radius:6px;padding:0.5rem;margin-bottom:1rem;font-size:0.9rem;font-weight:600;color:var(--gold)">
                📚 <?= $u['total_resensi'] ?> Resensi
            </div>
            <a href="profil_publik.php?id=<?= $u['id'] ?>" class="btn btn-outline btn-full btn-sm">Lihat Profil</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include ('footer.php'); ?>
