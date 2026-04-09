<?php
include 'koneksi.php';
session_start();

// 1. Cek Login
if($_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login");
    exit();
}

// 2. Inisialisasi Keranjang
if(!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// --- 3. LOGIKA SISTEM (TAMBAH, KURANG, HAPUS, BAYAR) ---

// A. Tambah atau Tambah Kuantitas (+)
if(isset($_GET['aksi']) && $_GET['aksi'] == "tambah") {
    $id = $_GET['id_produk'];
    $data = mysqli_query($koneksi, "SELECT * FROM produk WHERE id_produk='$id'");
    $p = mysqli_fetch_assoc($data);

    if($p) {
        if(isset($_SESSION['keranjang'][$id])) {
            $_SESSION['keranjang'][$id]['qty'] += 1;
        } else {
            $_SESSION['keranjang'][$id] = [
                'nama'  => $p['nama_produk'],
                'harga' => $p['harga_satuan'],
                'qty'   => 1
            ];
        }
    }
    header("location:kasir.php");
    exit();
}

// B. Kurangi Kuantitas (-)
if(isset($_GET['aksi']) && $_GET['aksi'] == "kurang") {
    $id = $_GET['id_produk'];
    if(isset($_SESSION['keranjang'][$id])) {
        $_SESSION['keranjang'][$id]['qty'] -= 1;
        if($_SESSION['keranjang'][$id]['qty'] <= 0) {
            unset($_SESSION['keranjang'][$id]);
        }
    }
    header("location:kasir.php");
    exit();
}

// C. Hapus Item dari Keranjang
if(isset($_GET['aksi']) && $_GET['aksi'] == "hapus") {
    $id = $_GET['id_produk'];
    unset($_SESSION['keranjang'][$id]);
    header("location:kasir.php");
    exit();
}

// D. Proses Selesaikan Pembayaran
if(isset($_POST['proses_bayar'])) {
    if(!empty($_SESSION['keranjang'])) {
        $total_bayar = $_POST['total_bayar'];
        $id_admin = $_SESSION['id_admin'] ?? 1;
        $tgl = date('Y-m-d H:i:s');

        // Simpan ke tabel transaksi
        $query_t = "INSERT INTO transaksi (id_admin, tanggal_transaksi, total_pendapatan) 
                    VALUES ('$id_admin', '$tgl', '$total_bayar')";
        mysqli_query($koneksi, $query_t);
        $id_transaksi = mysqli_insert_id($koneksi);

        // Simpan ke detail_transaksi & Potong Stok
        foreach($_SESSION['keranjang'] as $id_p => $item) {
            $qty = $item['qty'];
            $subtotal = $item['harga'] * $qty;
            
            // Menggunakan nama kolom sesuai database: jumlah_produk & subtotal
            $query_d = "INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah_produk, subtotal) 
                        VALUES ('$id_transaksi', '$id_p', '$qty', '$subtotal')";
            mysqli_query($koneksi, $query_d);

            // Update stok di tabel produk
            mysqli_query($koneksi, "UPDATE produk SET stok = stok - $qty WHERE id_produk = '$id_p'");
        }

        unset($_SESSION['keranjang']);
        echo "<script>
            alert('PEMBAYARAN BERHASIL!\\nTotal: Rp " . number_format($total_bayar, 0, ',', '.') . "');
            window.location='kasir.php';
        </script>";
    }
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