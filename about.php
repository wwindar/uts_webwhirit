<?php

session_start();
require_once ('db.php');
require_once ('auth.php');

requireLogin();

$pageTitle = 'Tentang';
$basePath = '../';
?>
<?php include ('header.php'); ?>

<div class="main-content">
    <div class="page-header">
        <h1>📖 Tentang Aplikasi</h1>
        <p>Informasi aplikasi dan profil pembuat.</p>
    </div>

    <div class="about-grid" style="display:grid;gap:1.5rem;align-items:start">

        <div style="background:var(--paper);border:1px solid var(--border);border-top:3px solid var(--gold);border-radius:4px;padding:1.8rem;box-shadow:0 4px 20px var(--shadow)">
            <div style="text-align:center;margin-bottom:1.5rem">
                <div style="font-size:3rem;margin-bottom:0.5rem">📚</div>
                <h2 style="font-family:var(--font-display);font-size:1.4rem;color:var(--ink)">
                    Resensi<em style="color:var(--gold)">Buku</em>
                </h2>
                <p style="color:var(--ink-light);font-size:0.85rem;margin-top:0.3rem">Katalog Ulasan Buku Digital</p>
            </div>

            <div style="border-top:1px solid var(--border);padding-top:1.2rem">
                <p style="font-size:0.9rem;color:var(--ink);line-height:1.7;margin-bottom:1rem">
                    Aplikasi <strong>Katalog Resensi Buku</strong> adalah platform digital untuk mencatat
                    dan mengelola ulasan buku secara terorganisir. Pengguna dapat menambahkan, mengedit,
                    dan menghapus resensi buku favorit mereka lengkap dengan rating bintang.
                </p>

                <h3 style="font-family:var(--font-display);font-size:1rem;color:var(--brown);margin-bottom:0.75rem">✨ Fitur Utama</h3>
                <?php
                $fitur = [
                    '📝 Tambah & kelola resensi buku',
                    '⭐ Sistem rating bintang 1–5',
                    '🔍 Pencarian & filter berdasarkan genre',
                    '📊 Dashboard statistik koleksi',
                    '🔒 Autentikasi pengguna dengan session',
                    '📱 Tampilan responsif untuk mobile',
                ];
                foreach ($fitur as $f): ?>
                    <div style="padding:0.45rem 0;border-bottom:1px solid var(--border);font-size:0.88rem;color:var(--ink)">
                        <?= $f ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top:1.2rem">
                <h3 style="font-family:var(--font-display);font-size:1rem;color:var(--brown);margin-bottom:0.75rem">🛠️ Teknologi</h3>
                <div style="display:flex;flex-wrap:wrap;gap:0.4rem">
                    <?php
                    $tech = ['PHP Native', 'MySQL', 'HTML5', 'CSS3', 'JavaScript', 'Git & GitHub'];
                    foreach ($tech as $t): ?>
                        <span style="background:var(--ink);color:var(--gold);padding:0.25rem 0.7rem;border-radius:20px;font-size:0.78rem;font-weight:500">
                            <?= $t ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div style="background:var(--paper);border:1px solid var(--border);border-top:3px solid var(--gold);border-radius:4px;padding:1.8rem;box-shadow:0 4px 20px var(--shadow)">
            <div style="text-align:center;margin-bottom:1.5rem">
                <div style="width:80px;height:80px;background:linear-gradient(135deg,var(--gold),var(--brown));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;font-size:2.2rem;border:3px solid var(--border)">
                    🎓
                </div>
                <h2 style="font-family:var(--font-display);font-size:1.3rem;color:var(--ink)">Windar Shineta</h2>
                <span style="font-size:0.78rem;color:var(--brown);background:rgba(212,168,67,0.12);border:1px solid rgba(212,168,67,0.3);border-radius:20px;padding:0.2rem 0.75rem">
                    Mahasiswa Informatika
                </span>
            </div>

            <div style="border-top:1px solid var(--border);padding-top:1.2rem">
                <?php
                $info = [
                    ['🏫', 'Institusi',   'Universitas Muhammadiyah PKU Surakarta'],
                    ['📚', 'Mata Kuliah', 'Praktikum Pemrograman Web 1'],
                    ['📅', 'Tahun',       '2025 / 2026'],
                    ['🔗', 'GitHub',      'github.com/wwindar/uts_webwhirit'],
                ];
                foreach ($info as [$icon, $label, $value]): ?>
                    <div style="display:flex;gap:0.75rem;padding:0.7rem 0;border-bottom:1px solid var(--border);align-items:flex-start">
                        <span style="font-size:1.1rem;margin-top:0.1rem"><?= $icon ?></span>
                        <div>
                            <div style="font-size:0.75rem;font-weight:500;color:var(--ink-light);text-transform:uppercase;letter-spacing:0.06em"><?= $label ?></div>
                            <div style="font-size:0.9rem;color:var(--ink);margin-top:0.15rem">
                                <?php if ($label === 'GitHub'): ?>
                                    <a href="https://<?= $value ?>" target="_blank"
                                       style="color:var(--brown);text-decoration:none;font-weight:500">
                                        <?= $value ?>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars($value) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top:1.2rem;background:var(--cream);border:1px solid var(--border);border-left:3px solid var(--gold);border-radius:3px;padding:1rem">
                <p style="font-size:0.88rem;color:var(--ink-light);line-height:1.7;font-family:var(--font-display);font-style:italic">
                    "Aplikasi ini dibuat sebagai project UAS Praktikum Pemrograman Web 1,
                    menerapkan konsep CRUD, autentikasi, dan version control dengan Git."
                </p>
            </div>

            <div style="margin-top:1rem">
                <a href="https://github.com/wwindar/uts_webwhirit" target="_blank"
                   class="btn btn-primary btn-full" style="text-align:center">
                    🔗 Lihat Repository GitHub
                </a>
            </div>
        </div>

    </div>
</div>

<?php include ('footer.php'); ?>