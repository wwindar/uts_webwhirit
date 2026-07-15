<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireAdmin();

$pageTitle = 'Kelola Pengguna';
$error = '';
$success = '';

// Handle POST actions (Update Role / Delete User)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        // Prevent action on self
        if ($userId === $_SESSION['user_id']) {
            $error = 'Anda tidak dapat mengubah peran atau menghapus akun Anda sendiri.';
        } else {
            if ($action === 'update_role') {
                $newRole = $_POST['role'] ?? '';
                if (in_array($newRole, ['user', 'admin'])) {
                    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $stmt->bind_param("si", $newRole, $userId);
                    if ($stmt->execute()) {
                        $success = 'Peran pengguna berhasil diperbarui.';
                    } else {
                        $error = 'Gagal memperbarui peran pengguna.';
                    }
                    $stmt->close();
                } else {
                    $error = 'Peran tidak valid.';
                }
            } elseif ($action === 'delete_user') {
                // Delete user from database
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                if ($stmt->execute()) {
                    $success = 'Akun pengguna berhasil dihapus secara permanen.';
                } else {
                    $error = 'Gagal menghapus akun pengguna.';
                }
                $stmt->close();
            }
        }
    }
}

// Fetch all users
$result = $conn->query("SELECT id, username, nama_lengkap, email, nomor_telepon, role, created_at FROM users ORDER BY username ASC");
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>
<?php include ('header.php'); ?>

<div class="main-content">
    <div class="page-header" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1>🛠️ Kelola Pengguna (Admin Panel)</h1>
            <p>Kelola peran pengguna aplikasi atau hapus akun pengguna secara permanen.</p>
        </div>
        <a href="backup_db.php" class="btn btn-outline" style="border-color:#2980b9; color:#2980b9;">💾 Backup Database</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div style="background:var(--paper); border:1px solid var(--border); border-top:3px solid var(--gold); border-radius:8px; padding:1.5rem; box-shadow:0 4px 20px var(--shadow);">
        <h2 style="font-family:var(--font-display); font-size:1.3rem; color:var(--ink); margin-bottom:1.2rem;">Daftar Pengguna Aplikasi</h2>

        <div class="table-wrapper" style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; text-align:left;">
                <thead>
                    <tr style="border-bottom:2px solid var(--border);">
                        <th style="padding:0.75rem; font-weight:600; color:var(--ink-light);">Username</th>
                        <th style="padding:0.75rem; font-weight:600; color:var(--ink-light);">Nama Lengkap</th>
                        <th style="padding:0.75rem; font-weight:600; color:var(--ink-light);">Kontak</th>
                        <th style="padding:0.75rem; font-weight:600; color:var(--ink-light);">Bergabung</th>
                        <th style="padding:0.75rem; font-weight:600; color:var(--ink-light); width:150px;">Peran (Role)</th>
                        <th style="padding:0.75rem; font-weight:600; color:var(--ink-light); text-align:center; width:180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr style="border-bottom:1px solid var(--border); transition:background 0.2s;" onmouseover="this.style.background='#fcfbfc'" onmouseout="this.style.background='none'">
                        <td style="padding:0.75rem;">
                            <div style="font-weight:600; color:var(--ink);">
                                <a href="profil_publik.php?id=<?= $u['id'] ?>" style="color:inherit; text-decoration:none;">@<?= htmlspecialchars($u['username']) ?></a>
                            </div>
                            <?php if ($u['id'] === $_SESSION['user_id']): ?>
                                <span style="font-size:0.7rem; background:#ebf8ff; color:#2b6cb0; padding:0.1rem 0.4rem; border-radius:4px; font-weight:bold;">Saya</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:0.75rem; color:var(--ink-light);"><?= htmlspecialchars($u['nama_lengkap'] ?: '-') ?></td>
                        <td style="padding:0.75rem; font-size:0.85rem; color:var(--ink-light);">
                            <div>✉ <?= htmlspecialchars($u['email'] ?: '-') ?></div>
                            <div>📞 <?= htmlspecialchars($u['nomor_telepon'] ?: '-') ?></div>
                        </td>
                        <td style="padding:0.75rem; font-size:0.85rem; color:var(--ink-light);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td style="padding:0.75rem;">
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="action" value="update_role">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="role" onchange="this.form.submit()" style="padding:0.4rem; border:1px solid var(--border); border-radius:6px; background:#fff; font-family:inherit; font-size:0.85rem; cursor:pointer;" <?= $u['id'] === $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                    <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </form>
                        </td>
                        <td style="padding:0.75rem; text-align:center;">
                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun @<?= htmlspecialchars($u['username']) ?> secara permanen? Seluruh data resensi dan relasi pengikut miliknya juga akan terhapus.');">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="color:#e53e3e; border-color:#feb2b2;" <?= $u['id'] === $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                    🗑️ Hapus Akun
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include ('footer.php'); ?>
</body>
</html>
