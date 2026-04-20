<?php
include 'koneksi.php';
session_start();

if($_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login");
    exit();
}

if(!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

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


if(isset($_GET['aksi']) && $_GET['aksi'] == "hapus") {
    $id = $_GET['id_produk'];
    unset($_SESSION['keranjang'][$id]);
    header("location:kasir.php");
    exit();
}


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
                $subtotal = $item['harga'] * $qty;

               
                mysqli_query($koneksi, "UPDATE produk SET stok = stok - $qty WHERE id_produk = '$id_produk'");
                
                
                mysqli_query($koneksi, "INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah_produk, subtotal) 
                                        VALUES ('$id_transaksi', '$id_produk', '$qty', '$subtotal')");
            }       
            
            unset($_SESSION['keranjang']);
            echo "<script> 
                    alert('PEMBAYARAN BERHASIL! stok telah diperbarui.');
                    window.location='kasir.php';
                  </script>";
        } 
    }
}
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
</head>
<body>

    <div class="sidebar" id="sidebar">
        <div>
            <div class="logo">2 PAKSI</div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link"><i class="fa-solid fa-gauge"></i> Beranda</a>
                <a href="kasir.php" class="nav-link active"><i class="fa-solid fa-cash-register"></i> Kasir</a>
                <a href="stok.php" class="nav-link"><i class="fa-solid fa-box"></i> Stok Barang</a>
                <a href="laporan.php" class="nav-link"><i class="fa-solid fa-file-invoice-dollar"></i> Laporan</a>
            </div>
        </div>
        <div class="logout-section">
            <a href="logout.php" class="nav-link logout-link"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
        </div>
    </div>

    <div class="content">
        <header class="header">
            <h1>Kasir Penjualan</h1>
        </header>
        
        <div class="main-grid">
            <div class="produk-grid">
                <?php 
                $res = mysqli_query($koneksi, "SELECT * FROM produk WHERE stok > 0");
                while($p = mysqli_fetch_assoc($res)): 
                ?>
                <a href="?aksi=tambah&id_produk=<?= $p['id_produk'] ?>" class="card-produk">
                    <!-- <img src="assets/img/<?= $p['gambar_produk'] ?>" onerror="this.src='https://via.placeholder.com/150?text=Produk'"> -->
                     <img src="assets/img/<?= $p['gambar_produk'] ?>" alt="Produk">
                    <h4><?= $p['nama_produk'] ?></h4>
                    <p>Rp <?= number_format($p['harga_satuan'], 0, ',', '.') ?></p>
                    <small>Stok: <?= $p['stok'] ?></small>
                </a>
                <?php endwhile; ?>
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
                        $sub = $item['harga'] * $item['qty'];
                        $total += $sub;
                    ?>
                        <div class="item">
                            <div>
                                <b><?= $item['nama'] ?></b>
                                <div class="qty-btns">
                                    <a href="?aksi=kurang&id_produk=<?= $id ?>" class="btn-small"><i class="fa-solid fa-minus"></i></a>
                                    <span><?= $item['qty'] ?></span>
                                    <a href="?aksi=tambah&id_produk=<?= $id ?>" class="btn-small"><i class="fa-solid fa-plus"></i></a>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <b>Rp <?= number_format($sub, 0, ',', '.') ?></b><br>
                                <a href="?aksi=hapus&id_produk=<?= $id ?>" style="color: #ef4444; font-size: 11px;"><i class="fa-solid fa-trash"></i> Hapus</a>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <div class="cart-footer">
                    <div class="total-row">
                        <span>Total</span>
                        <span>Rp <?= number_format($total, 0, ',', '.') ?></span>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="total_bayar" value="<?= $total ?>">
                        <button type="submit" name="proses_bayar" class="btn-bayar" <?= ($total == 0) ? 'disabled' : '' ?>>
                            Selesaikan Pembayaran
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>