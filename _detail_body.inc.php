<?php
// Buy Links
$hasBuyLinks = !empty($buku['link_tokopedia']) || !empty($buku['link_shopee']) || !empty($buku['link_gramedia']);

if ($hasBuyLinks):
?>
<div class="form-card" style="margin-top:2rem">
    <h3 style="margin-bottom:1rem;font-family:var(--font-head);font-size:1.15rem">🛒 Beli Buku Ini</h3>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap">
        <?php if (!empty($buku['link_tokopedia'])): ?>
            <a href="<?= htmlspecialchars($buku['link_tokopedia']) ?>" target="_blank" class="btn" style="background:#42b549;color:#fff;border:none">Tokopedia</a>
        <?php endif; ?>
        <?php if (!empty($buku['link_shopee'])): ?>
            <a href="<?= htmlspecialchars($buku['link_shopee']) ?>" target="_blank" class="btn" style="background:#ee4d2d;color:#fff;border:none">Shopee</a>
        <?php endif; ?>
        <?php if (!empty($buku['link_gramedia'])): ?>
            <a href="<?= htmlspecialchars($buku['link_gramedia']) ?>" target="_blank" class="btn" style="background:#205a3b;color:#fff;border:none">Gramedia</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
// Recommendations
$currentGenre = $buku['genre'] ?? '';
$currentId = $buku['id'] ?? 0;

if (!empty($currentGenre)) {
    $stmtReco = $conn->prepare("SELECT id, judul_buku, penulis, foto, rating FROM resensi WHERE genre = ? AND id != ? AND rating >= 3 ORDER BY rating DESC, tgl_input DESC LIMIT 4");
    $stmtReco->bind_param("si", $currentGenre, $currentId);
    $stmtReco->execute();
    $resReco = $stmtReco->get_result();

    if ($resReco->num_rows > 0):
?>
<div class="form-card" style="margin-top:2rem">
    <h3 style="margin-bottom:1rem;font-family:var(--font-head);font-size:1.15rem">✨ Rekomendasi Buku Serupa</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(160px, 1fr));gap:1rem">
        <?php while ($reco = $resReco->fetch_assoc()): ?>
            <a href="detail.php?id=<?= $reco['id'] ?>" style="text-decoration:none;color:inherit;border:1px solid var(--border);border-radius:8px;padding:0.75rem;display:flex;flex-direction:column;gap:0.5rem;transition:transform 0.2s, box-shadow 0.2s" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                <?php if (!empty($reco['foto']) && file_exists('uploads/' . $reco['foto'])): ?>
                    <img src="uploads/<?= htmlspecialchars($reco['foto']) ?>" alt="<?= htmlspecialchars($reco['judul_buku']) ?>" style="width:100%;height:220px;object-fit:cover;border-radius:6px">
                <?php else: ?>
                    <div style="width:100%;height:220px;background:#f0f0f0;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#999;font-size:0.8rem">No Cover</div>
                <?php endif; ?>
                <div style="font-weight:600;font-size:0.95rem;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                    <?= htmlspecialchars($reco['judul_buku']) ?>
                </div>
                <div style="font-size:0.85rem;color:var(--ink-light)">
                    <?= renderStars($reco['rating']) ?>
                </div>
            </a>
        <?php endwhile; ?>
    </div>
</div>
<?php
    endif;
    $stmtReco->close();
}
?>
