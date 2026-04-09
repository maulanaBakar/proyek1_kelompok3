<?php 
session_start();
if($_SESSION['status'] != "login"){
    header("location:login.php");
}
?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard 2 Paksi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="dashboard.css">
  </head>
  <body>

    <input type="checkbox" id="check-menu">

    <div class="bar-atas-mobile">
      <div class="nama-toko">2 PAKSI</div>
      <label for="check-menu" class="tombol-buka">
        <i class="fa-solid fa-bars"></i>
      </label>
    </div>

    <aside class="menu-samping">
      <div class="bagian-atas">
        <div class="judul-logo">2 PAKSI</div>
        <nav class="daftar-menu">
          <a href="dashboard.php" class="link-menu aktif">
            <i class="fa-solid fa-house"></i> Beranda
          </a>
          <a href="kasir.php" class="link-menu">
            <i class="fa-solid fa-cash-register"></i> Kasir
          </a>
          <a href="stok.php" class="link-menu">
            <i class="fa-solid fa-box"></i> Stok Barang
          </a>
          <a href="management.html" class="link-menu">
            <i class="fa-solid fa-file-lines"></i> Laporan
          </a>
        </nav>
      </div>
      
      <div class="bagian-bawah">
        <a href="logout.php" class="link-menu keluar">
          <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar
        </a>
      </div>
    </aside>

    <main class="isi-halaman">
      <header class="judul-halaman">
        <h1>Ringkasan Penjualan</h1>
      </header>

      <div class="baris-kotak">
        <div class="kotak-info">
          <div class="label-kecil">PENDAPATAN HARI INI</div>
          <div class="angka-besar">Rp 1.500.000</div>
        </div>
        <div class="kotak-info">
          <div class="label-kecil">TOTAL TRANSAKSI</div>
          <div class="angka-besar">25 <span>Pesanan</span></div>
        </div>
        <div class="kotak-info">
          <div class="label-kecil">VARIAN PRODUK</div>
          <div class="angka-besar">12 <span>Jenis</span></div>
        </div>
      </div>

      <div class="konten-bawah">
        <div class="kotak-putih">
          <h3 class="judul-sub">Grafik Unit Terjual</h3>
          
          <div class="wadah-grafik">
            <div class="item-grafik">
              <div class="teks-grafik"><span>Krupuk Ikan</span> <span>150</span></div>
              <div class="jalur-bar"><div class="isi-bar" style="width: 90%;"></div></div>
            </div>

            <div class="item-grafik">
              <div class="teks-grafik"><span>Krupuk Udang</span> <span>120</span></div>
              <div class="jalur-bar"><div class="isi-bar" style="width: 75%;"></div></div>
            </div>

            <div class="item-grafik">
              <div class="teks-grafik"><span>Krupuk Kulit</span> <span>95</span></div>
              <div class="jalur-bar"><div class="isi-bar" style="width: 60%;"></div></div>
            </div>
          </div>
        </div>

        <div class="kotak-putih">
          <h3 class="judul-sub">Produk Terlaris</h3>
          
          <div class="item-produk">
            <div class="foto-produk"></div>
            <div class="info-produk">
              <h4>Krupuk Ikan Tenggiri</h4>
              <p>150 Terjual</p>
            </div>
            <span class="nomor-urut">#1</span>
          </div>

          <div class="item-produk">
            <div class="foto-produk"></div>
            <div class="info-produk">
              <h4>Krupuk Udang</h4>
              <p>120 Terjual</p>
            </div>
            <span class="nomor-urut">#2</span>
          </div>
        </div>
      </div>
    </main>

  </body>
</html>