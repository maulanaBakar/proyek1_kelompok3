<?php 
session_start();
if($_SESSION['status'] != "login"){
    header("location:login.php");
}
?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>2 Paksi | Kasir</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="kasir.css">
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
          <a href="dashboard.php" class="link-menu">
            <i class="fa-solid fa-house"></i> Beranda
          </a>
          <a href="kasir.php" class="link-menu aktif">
            <i class="fa-solid fa-cash-register"></i> Kasir
          </a>
          <a href="#" class="link-menu">
            <i class="fa-solid fa-box"></i> Stok Barang
          </a>
          <a href="#" class="link-menu">
            <i class="fa-solid fa-file-lines"></i> Laporan
          </a>
        </nav>
      </div>
      
      <div class="bagian-bawah">
        <a href="#" class="link-menu keluar">
          <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar
        </a>
      </div>
    </aside>

    <main class="isi-halaman">
      <header class="judul-halaman">
        <h1>Kasir Penjualan</h1>
      </header>
        
      <div class="konten-bawah">
        <div class="grid-produk">
            <div class="kotak-putih kartu-produk">
                <div class="foto-produk"></div>
                <h4 class="nama-produk">Krupuk Ikan Tenggiri</h4>
                <p class="harga-produk">Rp 15.000</p>
                <button class="tombol-aksi">Tambah</button>
            </div>

            <div class="kotak-putih kartu-produk">
                <div class="foto-produk"></div>
                <h4 class="nama-produk">Krupuk Udang Super</h4>
                <p class="harga-produk">Rp 20.000</p>
                <button class="tombol-aksi">Tambah</button>
            </div>
        </div>

        <div class="kotak-putih keranjang">
            <h3 class="judul-sub">Item Terpilih</h3>
            <div class="list-keranjang">
                <div class="item-keranjang">
                    <div>
                        <b style="font-size: 13px;">Krupuk Ikan</b>
                        <div class="atur-qty">
                            <button class="btn-qty">-</button>
                            <span>2</span>
                            <button class="btn-qty">+</button>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <b style="font-size: 13px;">Rp 30.000</b><br>
                        <small style="color: red; cursor: pointer;">Hapus</small>
                    </div>
                </div>
            </div>

            <div class="total-belanja">
                <div class="baris-total">
                    <span>Total</span>
                    <span class="total-angka">Rp 30.000</span>
                </div>
                <button class="tombol-bayar">Selesaikan Pembayaran</button>
            </div>
        </div>
      </div>
    </main>

</body>
</html>