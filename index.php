<?php
session_start();
require_once('db.php');
require_once('auth.php');

$is_logged_in = isLoggedIn();
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
          <div class="navbar-nav ml-auto">
            <?php if ($is_logged_in): ?>
              <a class="nav-item btn btn-primary tombol" href="dashboard.php">Dashboard</a>
            <?php else: ?>
              <a class="nav-item btn btn-primary tombol" href="login.php">Join Us</a>
            <?php endif; ?>
          </div>
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
          <img src="https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&q=80&w=800" alt="workingspace" class="img-fluid">
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
          </ol>

          <!-- Wrapper for slides -->
          <div class="carousel-inner text-center">
            
            <div class="carousel-item active">
              <img src="win.jpeg" class="rounded-circle testimonial-img" alt="Klabkluen">
              <h5 class="testimonial-name">Klabkluen</h5>
              <p class="testimonial-role">Star In My Mind</p>
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <p class="testimonial-quote">
                    <span class="quote-icon">&ldquo;</span>
                    Apa yang bukan milikku, aku tidak akan coba mengambilnya. Apa yang menjadi milikku, aku tidak akan pernah membiarkan siapapun mengambilnya.
                    <span class="quote-icon">&rdquo;</span>
                  </p>
                </div>
              </div>
            </div>

            <div class="carousel-item">
              <img src="nda.jpeg" class="rounded-circle testimonial-img" alt="Windar">
              <h5 class="testimonial-name">Windar</h5>
              <p class="testimonial-role">Penulis</p>
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <p class="testimonial-quote">
                    <span class="quote-icon">&ldquo;</span>
                    Aku menyadari bahwa yang hilang itu tidak pernah hilang. Hal itu hanya hilang dari hati kita.
                    <span class="quote-icon">&rdquo;</span>
                  </p>
                </div>
              </div>
            </div>

            <div class="carousel-item">
              <img src="winda.jpeg" class="rounded-circle testimonial-img" alt="Winda">
              <h5 class="testimonial-name">Winda</h5>
              <p class="testimonial-role">Pembaca Setia</p>
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <p class="testimonial-quote">
                    <span class="quote-icon">&ldquo;</span>
                    Buku adalah jendela dunia, dan setiap resensi di sini membuka lembaran baru yang menginspirasi langkah hidupku.
                    <span class="quote-icon">&rdquo;</span>
                  </p>
                </div>
              </div>
            </div>

            <div class="carousel-item">
              <img src="win.jpeg" class="rounded-circle testimonial-img" alt="Klabkluen">
              <h5 class="testimonial-name">Klabkluen</h5>
              <p class="testimonial-role">Star In My Mind</p>
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
              <img src="nda.jpeg" class="rounded-circle testimonial-img" alt="Windar">
              <h5 class="testimonial-name">Windar</h5>
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
