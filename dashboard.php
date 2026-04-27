<?php 
session_start();
include 'koneksi.php'; // WAJIB sertakan koneksi

if($_SESSION['status'] != "login"){
    header("location:login.php");
}

// Hitung Pendapatan Hari Ini
$hari_ini = date('Y-m-d');
$query_pendapatan = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE DATE(tanggal_transaksi) = '$hari_ini'");
$data_pendapatan = mysqli_fetch_assoc($query_pendapatan);
$pendapatan_hari_ini = $data_pendapatan['total'] ?? 0;

//Hitung Total Transaksi Hari Ini
$query_transaksi = mysqli_query($koneksi, "SELECT COUNT(id_transaksi) as jumlah FROM transaksi WHERE DATE(tanggal_transaksi) = '$hari_ini'");
$data_transaksi = mysqli_fetch_assoc($query_transaksi);
$total_transaksi = $data_transaksi['jumlah'] ?? 0;

//Hitung Varian Produk yang Tersedia
$query_varian = mysqli_query($koneksi, "SELECT COUNT(id_produk) as total_produk FROM produk");
$data_varian = mysqli_fetch_assoc($query_varian);
$total_varian = $data_varian['total_produk'] ?? 0;
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
          <a href="laporan.php" class="link-menu">
            <i class="fa-solid fa-file-lines"></i> Laporan
          </a>
          <!-- <a href="pengaturan.php" class="link-menu">
           <i class="fa-solid fa-gear"></i> <span>Pengaturan</span>
          </a> -->

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
        <div class="angka-besar">Rp <?= number_format($pendapatan_hari_ini, 0, ',', '.') ?></div>
    </div>
    <div class="kotak-info">
        <div class="label-kecil">TOTAL TRANSAKSI</div>
        <div class="angka-besar"><?= $total_transaksi ?> <span>Pesanan</span></div>
    </div>
    <div class="kotak-info">
        <div class="label-kecil">VARIAN PRODUK</div>
        <div class="angka-besar"><?= $total_varian ?> <span>Jenis</span></div>
    </div>
</div>

      <div class="konten-bawah">
        <div class="kotak-putih">
          <h3 class="judul-sub">Grafik Unit Terjual</h3>
          
          <div class="kotak-putih">
    <h3 class="judul-sub">Grafik Unit Terjual</h3>
    <div class="wadah-grafik">
        <?php
        // Ambil 3 produk teratas berdasarkan jumlah terjual
        $query_grafik = mysqli_query($koneksi, "SELECT p.nama_produk, SUM(d.jumlah_produk) as total_terjual 
                                                FROM detail_transaksi d 
                                                JOIN produk p ON d.id_produk = p.id_produk 
                                                GROUP BY d.id_produk 
                                                ORDER BY total_terjual DESC LIMIT 3");
        
        while($g = mysqli_fetch_assoc($query_grafik)):
            // Logika sederhana untuk lebar bar (misal max terjual dianggap 200 unit untuk 100% width)
            $persen = ($g['total_terjual'] / 200) * 100; 
        ?>
        <div class="item-grafik">
            <div class="teks-grafik"><span><?= $g['nama_produk'] ?></span> <span><?= $g['total_terjual'] ?></span></div>
            <div class="jalur-bar"><div class="isi-bar" style="width: <?= $persen ?>%;"></div></div>
        </div>
        <?php endwhile; ?>
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