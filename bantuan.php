<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$pageTitle = 'Bantuan';
$basePath = '../';
?>
<?php include ('header.php'); ?>

<div class="main-content">
    <div class="page-header">
        <h1>❓ Pusat Bantuan</h1>
        <p>Pertanyaan yang sering diajukan seputar penggunaan aplikasi.</p>
    </div>

    <?php
    $faqs = [
        [
            'icon' => '🔑',
            'q'    => 'Lupa Kata Sandi?',
            'a'    => 'Saat ini fitur <em>reset password</em> otomatis belum tersedia. Jika kamu lupa kata sandi, mohon buat akun baru atau hubungi administrator untuk mereset kata sandimu secara manual.',
        ],
        [
            'icon' => '✏️',
            'q'    => 'Cara Menulis Resensi?',
            'a'    => 'Buka menu profil di pojok kanan atas, lalu pilih <strong>➕ Tulis Resensi</strong>. Lengkapi formulir detail buku, berikan rating bintang, tulis ulasanmu, lalu klik "Simpan Resensi".',
        ],
        [
            'icon' => '👤',
            'q'    => 'Cara Mengikuti Pengguna Lain?',
            'a'    => 'Klik nama pengguna penulis mana pun yang kamu temukan di Katalog atau Dashboard untuk membuka Profil Publik mereka, lalu tekan tombol <strong>Ikuti</strong>.',
        ],
        [
            'icon' => '💬',
            'q'    => 'Cara Mengirim Pesan Pribadi (DM)?',
            'a'    => 'Kamu bisa menuju menu <strong>💬 Kotak masuk</strong> di menu profilmu, atau masuk ke Profil Publik seseorang dan klik tombol <strong>Kirim Pesan</strong> untuk mulai mengobrol.',
        ],
        [
            'icon' => '🖼️',
            'q'    => 'Cara Mengubah Foto Profil?',
            'a'    => 'Buka halaman <strong>Profil Saya</strong>, klik tombol <strong>✏️ Edit Profil</strong>, lalu pilih foto baru. Kamu bisa memotong (crop) foto sebelum menyimpannya.',
        ],
        [
            'icon' => '🏷️',
            'q'    => 'Apa Bedanya Username dan Nama Tampilan?',
            'a'    => '<strong>Username</strong> adalah identitas unik akunmu (seperti di Instagram) — tidak bisa sama dengan pengguna lain dan hanya boleh huruf kecil, angka, titik, dan garis bawah. <strong>Nama Tampilan</strong> adalah nama yang akan terlihat di profil dan bisa diisi bebas.',
        ],
        [
            'icon' => '🔖',
            'q'    => 'Apa Bedanya Wishlist dan Bookmark?',
            'a'    => '<strong>Wishlist</strong> adalah daftar buku yang <em>ingin kamu baca</em> suatu hari nanti. <strong>Bookmark</strong> adalah resensi orang lain yang kamu simpan karena menarik atau ingin dibaca ulang.',
        ],
        [
            'icon' => '📤',
            'q'    => 'Cara Berbagi Profil?',
            'a'    => 'Buka halaman <strong>Profil Saya</strong> dan klik tombol <strong>🔗 Bagikan Profil</strong>. Akan muncul QR Code dan link yang bisa kamu salin dan kirim ke teman.',
        ],
    ];
    ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:1.25rem">
        <?php foreach ($faqs as $faq): ?>
        <div style="background:var(--paper);border:1px solid var(--border);border-top:3px solid var(--rose-dark);
                    border-radius:8px;padding:1.5rem;box-shadow:0 4px 15px var(--shadow);
                    transition:transform 0.2s,box-shadow 0.2s"
             onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px var(--shadow)'"
             onmouseout="this.style.transform='';this.style.boxShadow='0 4px 15px var(--shadow)'">
            <div style="font-size:1.6rem;margin-bottom:0.5rem"><?= $faq['icon'] ?></div>
            <h3 style="font-family:var(--font-display);font-size:1rem;color:var(--ink);margin-bottom:0.5rem">
                <?= htmlspecialchars($faq['q']) ?>
            </h3>
            <p style="font-size:0.875rem;color:var(--ink-light);line-height:1.65">
                <?= $faq['a'] ?>
            </p>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="margin-top:2.5rem;background:var(--paper);border:1px solid var(--border);
                border-left:4px solid var(--gold);border-radius:8px;padding:1.5rem;
                box-shadow:0 4px 15px var(--shadow);display:flex;gap:1rem;align-items:flex-start">
        <div style="font-size:2rem">📬</div>
        <div>
            <h3 style="font-family:var(--font-display);font-size:1rem;color:var(--ink);margin-bottom:0.3rem">
                Pertanyaanmu tidak ada di sini?
            </h3>
            <p style="font-size:0.875rem;color:var(--ink-light);line-height:1.65;margin-bottom:0.75rem">
                Kamu bisa menghubungi pembuat aplikasi langsung melalui GitHub atau fitur pesan di aplikasi ini.
            </p>
            <a href="about.php" class="btn btn-outline btn-sm">📖 Tentang Aplikasi</a>
        </div>
    </div>
</div>

<?php include ('footer.php'); ?>
