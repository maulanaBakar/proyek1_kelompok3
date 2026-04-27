<?php
include 'koneksi.php';
session_start();

// Cek Login
if($_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login");
    exit();
}

// Inisialisasi Keranjang
if(!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// 1. Logika Tambah Produk ke Keranjang
if(isset($_GET['aksi']) && $_GET['aksi'] == "tambah") {
    $id = $_GET['id_produk'];
    $data = mysqli_query($koneksi, "SELECT * FROM produk WHERE id_produk='$id'");
    $p = mysqli_fetch_assoc($data);

    if($p) {
        if(isset($_SESSION['keranjang'][$id])) {
            // Cek apakah stok masih mencukupi sebelum menambah qty di keranjang
            if ($_SESSION['keranjang'][$id]['qty'] < $p['stok']) {
                $_SESSION['keranjang'][$id]['qty'] += 1;
            } else {
                echo "<script>alert('Stok tidak mencukupi!');</script>";
            }
        } else {
            // Tambah baru dengan diskon
            $_SESSION['keranjang'][$id] = [
                'nama'   => $p['nama_produk'],
                'harga'  => $p['harga_satuan'],
                'qty'    => 1,
                'diskon' => $p['diskon'] 
            ];
        }
    }
    header("location:kasir.php"); 
    exit();
}

// 2. Logika Kurangi Qty
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

// 3. Logika Hapus Item
if(isset($_GET['aksi']) && $_GET['aksi'] == "hapus") {
    $id = $_GET['id_produk'];
    unset($_SESSION['keranjang'][$id]);
    header("location:kasir.php");
    exit();
}

// 4. Logika Proses Pembayaran (Simpan ke DB)
if(isset($_POST['proses_bayar'])) {
    if(!empty($_SESSION['keranjang'])) {
        $total_bayar = $_POST['total_bayar'];
        $tgl = date("Y-m-d H:i:s");

        $query_transaksi = "INSERT INTO transaksi (tanggal_transaksi, total_pendapatan) VALUES ('$tgl', '$total_bayar')";
        $simpan_transaksi = mysqli_query($koneksi, $query_transaksi);
        $id_transaksi = mysqli_insert_id($koneksi);

        if($simpan_transaksi) {
            foreach($_SESSION['keranjang'] as $id_produk => $item) {
                $qty = $item['qty'];
                // Hitung subtotal dengan diskon
                $diskon = isset($item['diskon']) ? $item['diskon'] : 0;
                $harga_setelah_diskon = $item['harga'] - ($item['harga'] * $diskon / 100);
                $subtotal = $harga_setelah_diskon * $qty;

                // Update Stok di DB
                mysqli_query($koneksi, "UPDATE produk SET stok = stok - $qty WHERE id_produk = '$id_produk'");
                
                // Simpan Detail Transaksi
                mysqli_query($koneksi, "INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah_produk, subtotal) 
                                        VALUES ('$id_transaksi', '$id_produk', '$qty', '$subtotal')");
            }       
            
            unset($_SESSION['keranjang']);
            echo "<script> 
                    alert('PEMBAYARAN BERHASIL! Stok telah diperbarui.');
                    window.location='kasir.php';
                  </script>";
        }   
    }
}

// 5. Logika Pencarian Produk
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>2 Paksi | Kasir Penjualan</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="kasir.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* Tambahan Style untuk Search Bar */
        .search-wrapper {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .search-wrapper input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-family: inherit;
        }
        .btn-cari {
            padding: 0 20px;
            background-color: #5c4033; /* Sesuaikan warna brand Anda */
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }
        .btn-reset {
            padding: 12px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            align-self: center;
        }
    </style>
</head>
<body>

    <aside class="menu-samping">
      <div class="bagian-atas">
        <div class="judul-logo">2 PAKSI</div>
        <nav class="daftar-menu">
          <a href="dashboard.php" class="link-menu"><i class="fa-solid fa-house"></i> Beranda</a>
          <a href="kasir.php" class="link-menu aktif"><i class="fa-solid fa-cash-register"></i> Kasir</a>
          <a href="stok.php" class="link-menu"><i class="fa-solid fa-box"></i> Stok Barang</a>
          <a href="laporan.php" class="link-menu"><i class="fa-solid fa-file-lines"></i> Laporan</a>
          <a href="pengaturan.php" class="link-menu">
           <!-- <i class="fa-solid fa-gear"></i> <span>Pengaturan</span> -->
          </a>
        </nav>
      </div>
      <div class="bagian-bawah">
        <a href="logout.php" class="link-menu keluar"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a>
      </div>
    </aside>

    <div class="content">
        <header class="header"><h1>Kasir Penjualan</h1></header>
        
        <div class="main-grid">
            <div class="produk-section">
                
                <form action="" method="GET" class="search-wrapper">
                    <input type="text" name="cari" placeholder="Cari produk atau kategori..." value="<?= htmlspecialchars($cari) ?>">
                    <button type="submit" class="btn-cari"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
                    <?php if($cari != ""): ?>
                        <a href="kasir.php" class="btn-reset">Reset</a>
                    <?php endif; ?>
                </form>

                <div class="produk-grid">
                    <?php 
                    // Query Pencarian
                    $sql = "SELECT * FROM produk WHERE stok > 0";
                    if ($cari != "") {
                        $sql .= " AND (nama_produk LIKE '%$cari%' OR kategori LIKE '%$cari%')";
                    }
                    
                    $res = mysqli_query($koneksi, $sql);
                    
                    if(mysqli_num_rows($res) > 0):
                        while($p = mysqli_fetch_assoc($res)): 
                            $path_gambar = (!empty($p['gambar_produk'])) ? 'uploads/' . $p['gambar_produk'] : 'uploads/no-image.png';
                    ?>
                    <a href="?aksi=tambah&id_produk=<?= $p['id_produk'] ?>" class="card-produk">
                        <img src="<?= $path_gambar ?>" alt="<?= htmlspecialchars($p['nama_produk']) ?>">
                        <h4><?= htmlspecialchars($p['nama_produk']) ?></h4>
                        <p>Rp <?= number_format($p['harga_satuan'], 0, ',', '.') ?></p>
                        <small>Stok: <?= $p['stok'] ?></small>
                    </a>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                        <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">
                            <i class="fa-solid fa-box-open" style="font-size: 48px; display: block; margin-bottom: 10px;"></i>
                            Produk tidak ditemukan.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="cart-panel">
                <div class="cart-header">Item Terpilih</div>
                <div class="cart-items">
                    <?php 
                    $total = 0;
                    if(empty($_SESSION['keranjang'])): ?>
                        <div style="text-align:center; padding: 50px 0; color: #D2C4C3;">
                            <i class="fa-solid fa-basket-shopping" style="font-size: 36px; margin-bottom: 12px;"></i>
                            <p>Belum ada produk</p>
                        </div>
                    <?php else: 
                        foreach($_SESSION['keranjang'] as $id => $item): 
                            $diskon = isset($item['diskon']) ? $item['diskon'] : 0;
                            $harga_final = $item['harga'] - ($item['harga'] * $diskon / 100);
                            $sub = $harga_final * $item['qty'];
                            $total += $sub;
                    ?>
                        <div class="item" style="border-bottom: 1px solid #eee; padding: 10px 0; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <b><?= htmlspecialchars($item['nama']) ?></b>
                                <div class="qty-btns">
                                    <a href="?aksi=kurang&id_produk=<?= $id ?>" class="btn-small"><i class="fa-solid fa-minus"></i></a>
                                    <span style="margin: 0 10px;"><?= $item['qty'] ?></span>
                                    <a href="?aksi=tambah&id_produk=<?= $id ?>" class="btn-small"><i class="fa-solid fa-plus"></i></a>
                                </div>
                            </div>
                            
                            <div style="text-align: right;">
                                <?php if($diskon > 0): ?>
                                    <small style="text-decoration: line-through; color: #999; font-size: 11px;">
                                        Rp <?= number_format($item['harga'], 0, ',', '.') ?>
                                    </small><br>
                                    <b style="color: #27ae60;">Rp <?= number_format($harga_final, 0, ',', '.') ?></b>
                                <?php else: ?>
                                    <b>Rp <?= number_format($item['harga'], 0, ',', '.') ?></b>
                                <?php endif; ?>
                                <br>
                                <a href="?aksi=hapus&id_produk=<?= $id ?>" style="color: #ef4444; font-size: 11px; text-decoration: none;"><i class="fa-solid fa-trash"></i> Hapus</a>
                            </div>
                        </div>
                    <?php endforeach; 
                    endif; ?>
                </div>

                <div class="cart-footer">
                    <div class="total-row">
                        <span>Total</span>
                        <span>Rp <?= number_format($total, 0, ',', '.') ?></span>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="total_bayar" value="<?= $total ?>">
                        <button type="submit" name="proses_bayar" class="btn-bayar" <?= ($total == 0) ? 'disabled' : '' ?> onclick="return confirm('Proses pembayaran sekarang?')">
                            Selesaikan Pembayaran
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>