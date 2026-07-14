<?php
session_start();
require_once('db.php');
require_once('auth.php');

$pageTitle = 'Beranda';
?>
<?php include('header.php'); ?>

<main class="landing-main">
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Temukan Dunia Baru di Setiap <em>Halaman</em></h1>
            <p class="hero-subtitle">Bergabunglah dengan komunitas pembaca kami. Bagikan ulasan, temukan rekomendasi terbaik, dan diskusikan buku favorit Anda.</p>
            <div class="hero-actions">
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="btn btn-primary">Ke Dashboard</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary">Mulai Sekarang</a>
                    <a href="katalog.php" class="btn btn-secondary">Jelajahi Katalog</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="hero-visual">
            <div class="glass-card">
                <div class="glass-book">
                    <span class="book-icon">📖</span>
                    <h3>The Great Gatsby</h3>
                    <p>F. Scott Fitzgerald</p>
                    <div class="stars">★★★★★</div>
                </div>
                <div class="glass-review">
                    "Sebuah mahakarya yang menangkap esensi era jazz dengan sempurna."
                </div>
            </div>
        </div>
    </section>

    <section class="features-section">
        <div class="section-header">
            <h2>Kenapa Memilih Resensi<em>Buku</em>?</h2>
            <p>Platform terbaik untuk membagikan kecintaan Anda pada buku</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">✍️</div>
                <h3>Tulis Ulasan</h3>
                <p>Bagikan pendapat dan pandangan Anda mengenai buku yang baru saja selesai Anda baca.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔍</div>
                <h3>Temukan Rekomendasi</h3>
                <p>Jelajahi ribuan ulasan dari pembaca lain untuk menemukan bacaan Anda selanjutnya.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3>Bangun Komunitas</h3>
                <p>Ikuti pembaca dengan selera yang sama, diskusikan buku, dan kirim pesan secara langsung.</p>
            </div>
        </div>
    </section>
</main>

<style>
/* Inline styling specific to landing page to add that 'wow' factor */
.landing-main {
    flex: 1;
}

/* Hero Section */
.hero-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6rem 10%;
    background: radial-gradient(circle at 20% 30%, rgba(173, 103, 117, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(115, 115, 122, 0.1) 0%, transparent 50%);
    min-height: 80vh;
    gap: 4rem;
    overflow: hidden;
    position: relative;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: -50%; right: -20%;
    width: 800px; height: 800px;
    background: radial-gradient(circle, rgba(173,103,117,0.05) 0%, rgba(247,244,245,0) 70%);
    border-radius: 50%;
    z-index: -1;
}

.hero-content {
    flex: 1;
    max-width: 600px;
    animation: fadeUp 1s cubic-bezier(0.16, 1, 0.3, 1);
}

.hero-title {
    font-family: var(--font-display);
    font-size: 4rem;
    line-height: 1.1;
    color: var(--ink);
    margin-bottom: 1.5rem;
}

.hero-title em {
    color: var(--rose);
    font-style: italic;
}

.hero-subtitle {
    font-size: 1.2rem;
    color: var(--sage-dark);
    margin-bottom: 2.5rem;
    line-height: 1.8;
}

.hero-actions {
    display: flex;
    gap: 1rem;
}

.hero-actions .btn {
    padding: 1rem 2rem;
    font-size: 1.1rem;
    border-radius: 30px;
}

.hero-actions .btn-secondary {
    background: transparent;
    border: 2px solid var(--rose);
    color: var(--rose);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
    transition: all 0.3s ease;
}

.hero-actions .btn-secondary:hover {
    background: var(--rose-light);
    transform: translateY(-2px);
}

.hero-visual {
    flex: 1;
    position: relative;
    display: flex;
    justify-content: center;
    animation: fadeInScale 1.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.glass-card {
    background: rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.7);
    border-radius: 24px;
    padding: 2.5rem;
    box-shadow: 0 30px 60px rgba(43, 43, 48, 0.08);
    position: relative;
    max-width: 400px;
    width: 100%;
    transform: rotate(2deg);
    transition: transform 0.5s ease;
}

.glass-card:hover {
    transform: rotate(0deg) translateY(-10px);
}

.glass-book {
    text-align: center;
    margin-bottom: 2rem;
}

.book-icon {
    font-size: 4rem;
    display: block;
    margin-bottom: 1rem;
    filter: drop-shadow(0 10px 10px rgba(173, 103, 117, 0.2));
}

.glass-book h3 {
    font-family: var(--font-display);
    font-size: 1.8rem;
    color: var(--ink);
    margin-bottom: 0.5rem;
}

.glass-book p {
    color: var(--sage);
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.stars {
    color: #F59E0B;
    letter-spacing: 2px;
    font-size: 1.2rem;
}

.glass-review {
    background: rgba(255, 255, 255, 0.6);
    padding: 1.5rem;
    border-radius: 16px;
    font-style: italic;
    color: var(--ink-light);
    border-left: 4px solid var(--rose);
    position: relative;
}

/* Features Section */
.features-section {
    padding: 6rem 10%;
    background: var(--paper);
}

.section-header {
    text-align: center;
    margin-bottom: 4rem;
}

.section-header h2 {
    font-family: var(--font-display);
    font-size: 2.5rem;
    color: var(--ink);
    margin-bottom: 1rem;
}

.section-header h2 em {
    color: var(--rose);
    font-style: italic;
}

.section-header p {
    color: var(--sage);
    font-size: 1.1rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 3rem;
}

.feature-card {
    background: var(--cream);
    padding: 3rem 2rem;
    border-radius: 20px;
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.feature-card:hover {
    background: white;
    border-color: var(--rose-light);
    box-shadow: 0 20px 40px rgba(173, 103, 117, 0.08);
    transform: translateY(-10px);
}

.feature-icon {
    font-size: 3rem;
    margin-bottom: 1.5rem;
    display: inline-block;
    padding: 1rem;
    background: var(--rose-light);
    border-radius: 50%;
    width: 80px; height: 80px;
    line-height: 50px;
}

.feature-card h3 {
    font-family: var(--font-display);
    font-size: 1.5rem;
    color: var(--ink);
    margin-bottom: 1rem;
}

.feature-card p {
    color: var(--sage-dark);
    line-height: 1.6;
}

/* Animations */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeInScale {
    from { opacity: 0; transform: scale(0.9) rotate(-5deg); }
    to { opacity: 1; transform: scale(1) rotate(2deg); }
}

/* Responsive */
@media (max-width: 992px) {
    .hero-section {
        flex-direction: column;
        text-align: center;
        padding: 4rem 5%;
    }
    
    .hero-content {
        max-width: 100%;
    }
    
    .hero-actions {
        justify-content: center;
    }
    
    .hero-title {
        font-size: 3rem;
    }
    
    .glass-card {
        transform: rotate(0);
    }
    .glass-card:hover {
        transform: translateY(-10px);
    }
}

@media (max-width: 768px) {
    .hero-actions {
        flex-direction: column;
    }
    .hero-title {
        font-size: 2.5rem;
    }
    .features-section {
        padding: 4rem 5%;
    }
}
</style>

<?php include('footer.php'); ?>
