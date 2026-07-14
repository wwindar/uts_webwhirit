<?php
session_start();
require_once('db.php');
require_once('auth.php');

$is_logged_in = isLoggedIn();
$nav_username = $is_logged_in ? ($_SESSION['username'] ?? 'Akun') : '';
?>
<!doctype html>
<html lang="id">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <link rel="stylesheet" href="style.css?v=<?= time() ?>">

    <title>Beranda — Resensi Buku</title>

    <style>
    @import url('https://fonts.googleapis.com/css2?family=Viga&display=swap');

    /* Custom Fonts & Base styling */
    body {
        font-family: 'DM Sans', sans-serif;
        background-color: #ffffff;
        color: #2B2B30;
    }

    /* Navbar */
    .navbar {
        position: relative;
        z-index: 10;
        transition: background-color 0.3s;
        background: transparent !important;
        border-bottom: none !important;
        height: auto !important;
    }

    .navbar-brand {
        font-family: Viga;
        font-size: 24px;
        color: white !important;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);
    }

    /* Jumbotron */
    .jumbotron {
        background-image: url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&q=80&w=1920');
        background-size: cover;
        background-position: center;
        height: 540px;
        text-align: center;
        position: relative;
        margin-bottom: 0;
        display: flex;
        align-items: center;
    }

    .jumbotron::after {
        content: '';
        display: block;
        width: 100%;
        height: 100%;
        background-image: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0.4));
        position: absolute;
        top: 0;
        left: 0;
        z-index: 0;
    }

    .jumbotron .container {
        z-index: 1;
        position: relative;
    }

    .jumbotron .display-4 {
        color: white;
        font-weight: 200;
        text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.9);
        font-size: 36px;
        margin-bottom: 30px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .jumbotron .display-4 span {
        font-weight: 700;
        color: #AD6775; /* matching app's rose color */
    }

    /* Info Panel */
    .info-panel {
        box-shadow: 0 10px 30px rgba(43, 43, 48, 0.15);
        border-radius: 12px;
        margin-top: -80px;
        background-color: white;
        padding: 30px;
        position: relative;
        z-index: 5;
    }

    .info-panel .info-icon {
        font-size: 32px;
        margin-right: 15px;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #F7EBED; /* rose-light */
        border-radius: 50%;
        float: left;
        transition: transform 0.3s;
    }

    .info-panel .col-lg:hover .info-icon {
        transform: scale(1.1);
    }

    .info-panel h4 {
        font-size: 16px;
        text-transform: uppercase;
        font-weight: bold;
        margin-top: 5px;
        margin-bottom: 5px;
    }

    .info-panel h4 a {
        color: #2B2B30;
        text-decoration: none;
        transition: color 0.2s;
    }

    .info-panel h4 a:hover {
        color: #AD6775;
    }

    .info-panel p {
        font-size: 13px;
        color: #8C8C94;
        font-weight: 300;
        line-height: 1.4;
    }

    /* Workingspace */
    .workingspace {
        margin-top: 100px;
        text-align: center;
    }

    .workingspace img {
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        max-width: 100%;
        height: auto;
    }

    .workingspace h3 {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 32px;
        font-weight: 200;
        text-transform: uppercase;
        margin-top: 20px;
    }

    .workingspace h3 span {
        font-weight: 700;
        color: #AD6775;
    }

    .workingspace p {
        font-size: 16px;
        color: #8C8C94;
        font-weight: 300;
        margin: 20px 0;
        line-height: 1.6;
    }

    .workingspace .btn {
        background-color: #AD6775;
        border: none;
        padding: 10px 30px;
        border-radius: 20px;
        font-weight: bold;
        transition: background-color 0.2s, transform 0.2s;
        color: white;
    }

    .workingspace .btn:hover {
        background-color: #924D5A;
        transform: translateY(-2px);
    }

    /* Testimonial */
    .testimonial {
        margin-top: 80px;
        padding-bottom: 80px;
    }

    .testimonial-img {
        width: 90px;
        height: 90px;
        object-fit: cover;
        margin-bottom: 15px;
    }

    .testimonial-name {
        font-size: 18px;
        font-weight: bold;
        color: #2B2B30;
        margin-bottom: 2px;
    }

    .testimonial-role {
        font-size: 13px;
        color: #ACACAC;
        margin-bottom: 20px;
    }

    .testimonial-quote {
        font-size: 18px;
        font-weight: 400;
        font-style: italic;
        color: #666;
        line-height: 1.6;
        position: relative;
        display: inline-block;
        padding: 0 10px;
    }

    .quote-icon {
        font-size: 40px;
        color: #A5DBD3;
        font-weight: bold;
        line-height: 0;
        vertical-align: middle;
        position: relative;
        top: 15px;
        font-family: serif;
    }

    /* Carousel Indicators */
    .testimonial .carousel-indicators {
        bottom: -60px;
    }

    .testimonial .carousel-indicators li {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: transparent;
        border: 1px solid #4BBBA6;
        margin: 0 5px;
        cursor: pointer;
    }

    .testimonial .carousel-indicators li.active {
        background-color: #4BBBA6;
    }

    /* Utility */
    .tombol {
        text-transform: uppercase;
        border-radius: 40px;
        background-color: #AD6775 !important;
        border-color: #AD6775 !important;
        padding: 10px 30px;
        font-weight: bold;
        color: white !important;
        transition: transform 0.2s, background-color 0.2s;
    }

    .tombol:hover {
        background-color: #924D5A !important;
        border-color: #924D5A !important;
        transform: translateY(-2px);
    }

    /* DESKTOP VERSION */
    @media (min-width: 992px) {
        .navbar-brand {
            font-size: 32px;
        }

        .jumbotron {
            margin-top: -76px;
            height: 640px;
        }

        .jumbotron .display-4 {
            font-size: 56px;
        }

        .info-panel {
            margin-top: -100px;
        }

        .info-panel .info-icon {
            width: 80px;
            height: 80px;
            font-size: 40px;
        }

        .workingspace {
            text-align: left;
            margin-top: 120px;
        }
    }
    </style>
  </head>
  <body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
      <div class="container">
        <a class="navbar-brand" href="#">Whirit Wening Windar Shineta</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
          <ul class="navbar-nav ml-auto align-items-center" style="gap: 10px;">
            <?php if ($is_logged_in): ?>
              <li class="nav-item" style="color: rgba(255,255,255,0.85); font-size: 0.9rem; padding: 0 4px;">
                👤 <?= htmlspecialchars($nav_username) ?>
              </li>
              <li class="nav-item">
                <a class="nav-link btn btn-primary tombol px-4" href="dashboard.php" style="color: white !important;">Dashboard</a>
              </li>
              <li class="nav-item">
                <a class="nav-link btn tombol px-4" href="logout.php" style="background: rgba(255,255,255,0.15); border: 2px solid rgba(255,255,255,0.7); backdrop-filter: blur(4px); color: white !important;">🚪 Keluar</a>
              </li>
            <?php else: ?>
              <li class="nav-item">
                <a class="nav-link btn btn-primary tombol px-4" href="login.php" style="color: white !important;">Join Us</a>
              </li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
    <!-- akhir Navbar -->

    <!-- Jumbotron -->
    <div class="jumbotron jumbotron-fluid">
      <div class="container">
        <h1 class="display-4">Mari Membaca & <span>Berbagi Resensi Buku</span></h1>
        <?php if ($is_logged_in): ?>
          <a href="dashboard.php" class="btn btn-primary tombol">Dashboard</a>
        <?php else: ?>
          <a href="register.php" class="btn btn-primary tombol">Join Us</a>
        <?php endif; ?>
      </div>
    </div>
    <!-- akhir Jumbotron -->

    <!-- Container -->
    <div class="container">

      <!-- Info Panel -->
      <div class="row justify-content-center">
        <div class="col-lg-10 info-panel">
          <div class="row">
            <div class="col-lg">
              <span class="info-icon">📚</span>
              <h4><a href="<?= $is_logged_in ? 'katalog.php' : 'login.php' ?>">Katalog Resensi</a></h4>
              <p>Jelajahi ulasan buku mendalam dari berbagai pembaca.</p>
            </div>
            <div class="col-lg">
              <span class="info-icon">✍️</span>
              <h4><a href="<?= $is_logged_in ? 'tambah.php' : 'login.php' ?>">Tulis Ulasan</a></h4>
              <p>Bagikan penilaian dan opini jujur mengenai buku favorit Anda.</p>
            </div>
            <div class="col-lg">
              <span class="info-icon">📌</span>
              <h4><a href="<?= $is_logged_in ? 'wishlist.php' : 'login.php' ?>">Simpan Buku</a></h4>
              <p>Tambahkan buku pilihan Anda ke dalam Wishlist & Bookmark.</p>
            </div>
          </div>
        </div>
      </div>
      <!-- akhir Info Panel -->

      <!-- Workingspace -->
      <div class="row workingspace align-items-center">
        <div class="col-lg-6">
          <img src="workingspace.jpg" alt="workingspace" class="img-fluid" style="border-radius: 20px;">
        </div>
        <div class="col-lg-5 offset-lg-1">
          <h3>Mari Kita <span>Membaca</span> & <span>Berbagi</span> Resensi</h3>
          <p>Temukan rekomendasi bacaan terbaik, pelajari ulasan detail tentang berbagai genre buku, dan mulailah berdiskusi dengan sesama pecinta literatur di seluruh Indonesia.</p>
          <a href="<?= $is_logged_in ? 'katalog.php' : 'login.php' ?>" class="btn btn-primary tombol">Jelajahi Katalog</a>
        </div>
      </div>
      <!-- akhir Workingspace -->

      <!-- Testimonial -->
      <section class="testimonial">
        <div id="testimonialCarousel" class="carousel slide" data-ride="carousel">
          <!-- Indicators -->
          <ol class="carousel-indicators">
            <li data-target="#testimonialCarousel" data-slide-to="0" class="active"></li>
            <li data-target="#testimonialCarousel" data-slide-to="1"></li>
            <li data-target="#testimonialCarousel" data-slide-to="2"></li>
            <li data-target="#testimonialCarousel" data-slide-to="3"></li>
            <li data-target="#testimonialCarousel" data-slide-to="4"></li>
            <li data-target="#testimonialCarousel" data-slide-to="5"></li>
            <li data-target="#testimonialCarousel" data-slide-to="6"></li>
            <li data-target="#testimonialCarousel" data-slide-to="7"></li>
            <li data-target="#testimonialCarousel" data-slide-to="8"></li> 
            <li data-target="#testimonialCarousel" data-slide-to="9"></li>
            <li data-target="#testimonialCarousel" data-slide-to="10"></li>
          </ol>

          <!-- Wrapper for slides -->
          <div class="carousel-inner text-center">

            <div class="carousel-item active">
              <img src="win1.jfif" class="rounded-circle testimonial-img" alt="Hong Pichetpong">
              <h5 class="testimonial-name">Hong Pichetpong</h5>
              <p class="testimonial-role">LYKN</p>
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <p class="testimonial-quote">
                    <span class="quote-icon">&ldquo;</span>
                    Cowok ganteng yang kamu suka kagumi, mungkin nggak seganteng HONG LYKN
                    <span class="quote-icon">&rdquo;</span>
                  </p>
                </div>
              </div>
            </div> 

            <div class="carousel-item">
              <img src="win2.jfif" class="rounded-circle testimonial-img" alt="Qin">
              <h5 class="testimonial-name">Qin</h5>
              <p class="testimonial-role">Duang With You</p>
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <p class="testimonial-quote">
                    <span class="quote-icon">&ldquo;</span>
                    Orang bilang waktu akan menyembuhkan segalanya. Tapi mereka lupa bahwa sebelum sembuh, waktu akan memaksa kita untuk menelan memori-memori tajam yang mengiris tenggorokan dalam diam."
                    <span class="quote-icon">&rdquo;</span>
                  </p>
                </div>
              </div>
            </div>

            <div class="carousel-item">
              <img src="win3.jfif" class="rounded-circle testimonial-img" alt="Thame">
              <h5 class="testimonial-name">Thame</h5>
              <p class="testimonial-role">Thamepo</p>
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <p class="testimonial-quote">
                    <span class="quote-icon">&ldquo;</span>
                    Keberhasilan sejati bukanlah seberapa terang lampu panggung menyorotimu, melainkan seberapa tulus orang-orang di sekitarmu tetap merangkulmu saat lampu itu padam.
                    <span class="quote-icon">&rdquo;</span>
                  </p>
                </div>
              </div>
            </div>

            <div class="carousel-item">
              <img src="win4.jpg" class="rounded-circle testimonial-img" alt="Wind Lyricist">
              <h5 class="testimonial-name">Wind Lyricist</h5>
              <p class="testimonial-role">Penulis</p>
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <p class="testimonial-quote">
                    <span class="quote-icon">&ldquo;</span>
                    Membaca resensi di sini membuatku menemukan banyak dunia baru tanpa harus beranjak dari tempatku berada.
                    <span class="quote-icon">&rdquo;</span>
                  </p>
                </div>
              </div>
            </div>

            <div class="carousel-item">
              <img src="win5.jfif" class="rounded-circle testimonial-img" alt="Gun">
              <h5 class="testimonial-name">Gun</h5>
              <p class="testimonial-role">My School President</p>
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <p class="testimonial-quote">
                    <span class="quote-icon">&ldquo;</span>
                    Jangan menyerah pada musikmu hanya karena dunia sedang tidak mendengarkan hari ini. Teruskan, suatu hari suaramu akan sampai ke hati orang yang tepat.
                    <span class="quote-icon">&rdquo;</span>
                  </p>
                </div>
              </div>
            </div>

            <div class="carousel-item">
              <img src="win6.jfif" class="rounded-circle testimonial-img" alt="Duang">
              <h5 class="testimonial-name">Duang</h5>
              <p class="testimonial-role">Duang With You</p>
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <p class="testimonial-quote">
                    <span class="quote-icon">&ldquo;</span>
                    Cinta atau impian tidak harus selalu terlihat sempurna sejak awal. Terkadang, sesuatu yang 'tidak sempurna namun cukup baik' justru memberi ruang bagi kita untuk tumbuh bersama.
                    <span class="quote-icon">&rdquo;</span>
                  </p>
                </div>
              </div>
            </div>

            <div class="carousel-item">
              <img src="win7.jpeg" class="rounded-circle testimonial-img" alt="Wind Lyricist">
              <h5 class="testimonial-name">Wind Lyricist</h5>
              <p class="testimonial-role">Penulis</p>
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <p class="testimonial-quote">
                    <span class="quote-icon">&ldquo;</span>
                    Setiap orang memiliki waktu dan panggungnya masing-masing untuk bersinar. Jangan meredupkan cahayamu hanya karena melihat orang lain berjalan lebih cepat. Fokuslah pada jalan yang sedang kamu bangun sendiri.
                    <span class="quote-icon">&rdquo;</span>
                  </p>
                </div>
              </div>
            </div>

            <div class="carousel-item">
              <img src="win8.jfif" class="rounded-circle testimonial-img" alt="Wave">
              <h5 class="testimonial-name">Wave</h5>
              <p class="testimonial-role">The Gifted</p>
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <p class="testimonial-quote">
                    <span class="quote-icon">&ldquo;</span>
                    Menjadi pintar atau memiliki bakat luar biasa tidak ada gunanya jika kamu harus mengisolasi dirimu dari dunia. Manusia tetap butuh kepercayaan dan pelukan orang lain untuk tetap menjaga kewarasannya.
                    <span class="quote-icon">&rdquo;</span>
                  </p>
                </div>
              </div>
            </div>

            <div class="carousel-item">
              <img src="win9.jfif" class="rounded-circle testimonial-img" alt="Pran">
              <h5 class="testimonial-name">Pran</h5>
              <p class="testimonial-role">Bad Buddy Series</p>
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <p class="testimonial-quote">
                    <span class="quote-icon">&ldquo;</span>
                    Aku ingin memiliki cerita yang bisa kusebut milikku sendiri... dan aku juga ingin menjadi orang yang memegang kendali atas akhir dari cerita itu.
                    <span class="quote-icon">&rdquo;</span>
                  </p>
                </div>
              </div>
            </div>

            <div class="carousel-item">
              <img src="win10.jfif" class="rounded-circle testimonial-img" alt="White">
              <h5 class="testimonial-name">White</h5>
              <p class="testimonial-role">Not Me</p>
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <p class="testimonial-quote">
                    <span class="quote-icon">&ldquo;</span>
                    Hukum seharusnya menjadi perisai bagi mereka yang lemah, bukan menjadi pedang di tangan mereka yang berkuasa untuk menindas.
                    <span class="quote-icon">&rdquo;</span>
                  </p>
                </div>
              </div>
            </div>

            <div class="carousel-item">
              <img src="win11.jpeg" class="rounded-circle testimonial-img" alt="Wind Lyricist">
              <h5 class="testimonial-name">Wind Lyricist</h5>
              <p class="testimonial-role">Penulis</p>
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <p class="testimonial-quote">
                    <span class="quote-icon">&ldquo;</span>
                    Karya sastra selalu punya cara magis untuk menghubungkan perasaan kita. Platform ini menjadi ruang terbaiknya.
                    <span class="quote-icon">&rdquo;</span>
                  </p>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>
      <!-- akhir Testimonial -->

      <!-- Lihat Semua Resensi Button -->
      <div class="row justify-content-center text-center my-5">
        <div class="col-md-12">
          <a href="<?= $is_logged_in ? 'katalog.php' : 'login.php' ?>" class="btn btn-outline" style="border-radius: 20px; padding: 0.6rem 2rem;">Lihat Semua Resensi →</a>
        </div>
      </div>

    </div>
    <!-- akhir Container -->

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

    <?php include('footer.php'); ?>
