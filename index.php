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
        padding: 50px 20px;
        background-color: #F7F4F5;
        border-radius: 20px;
    }

    .testimonial h5 {
        text-align: center;
        font-weight: 300;
        font-style: italic;
        font-size: 20px;
        line-height: 1.6;
        color: #2B2B30;
    }

    .testimonial figure img {
        width: 60px;
        height: 60px;
        margin: 20px 10px 0px;
        opacity: 0.6;
        object-fit: cover;
        transition: opacity 0.3s;
    }

    .testimonial figure img.utama {
        width: 90px;
        height: 90px;
        opacity: 1;
        margin-top: 5px;
        border: 3px solid #AD6775;
    }

    .testimonial figure h5 {
        font-size: 16px;
        font-weight: bold;
        font-style: normal;
        color: #AD6775;
        margin-top: 10px;
    }

    .testimonial figure p {
        font-size: 12px;
        color: #ACACAC;
        margin-top: -5px !important;
    }

    .testimonial figcaption {
        text-align: center;
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
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <h5>"Aku menyadari bahwa yang hilang itu tidak pernah hilang. Hal itu hanya hilang dari hati kita."</h5>
          </div>
        </div>

        <div class="row justify-content-center">
          <div class="col-lg-6 justify-content-center d-flex">
            <figure class="figure">
              <img src="win.jpeg" class="figure-img img-fluid rounded-circle" alt="Testi 1">          
            </figure>

            <figure class="figure">
              <img src="nda.jpeg" class="figure-img img-fluid rounded-circle utama" alt="Testi 2">
              <figcaption class="figure-caption">
                <h5>Windar</h5>
                <p>Penulis</p>
              </figcaption>
            </figure>

            <figure class="figure">
              <img src="winda.jpeg" class="figure-img img-fluid rounded-circle" alt="Testi 3">
            </figure>
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
