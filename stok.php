<?php
include 'koneksi.php';
session_start();

if($_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login");
    exit();
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>2 Paksi | Management Inventori</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="stok.css">
</head>
<body>

    <input type="checkbox" id="check-menu" class="hide-input">
    
    <input type="checkbox" id="modal-trigger" class="hide-input">

    <div class="mobile-toggle">
        <div class="brand">2 PAKSI</div>
        <label for="check-menu" class="menu-btn"><i class="fa-solid fa-bars"></i></label>
    </div>

    <aside class="sidebar" id="sidebar">
        <div>
            <div class="logo">2 PAKSI</div>
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link"><i class="fa-solid fa-gauge"></i> Beranda</a>
                <a href="kasir.php" class="nav-link"><i class="fa-solid fa-cash-register"></i> Kasir</a>
                <a href="stok.php" class="nav-link active"><i class="fa-solid fa-box"></i> Stok Barang</a>
                <a href="laporan.php" class="nav-link"><i class="fa-solid fa-file-invoice-dollar"></i> Laporan</a>
            </nav>
        </div>
        <div class="logout-section">
            <a href="logout.php" class="nav-link logout-link"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
        </div>
    </aside>

    <main class="content">
        <header class="header">
            <h1>Management Inventori</h1>
            <label for="modal-trigger" class="btn-add">
                <i class="fa-solid fa-plus"></i> Tambah Produk
            </label>
        </header>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nama produk</th>
                        <th>Kategori</th>
                        <th>Harga jual</th>
                        <th>Stok</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $res = mysqli_query($koneksi, "SELECT * FROM produk");
                    while($row = mysqli_fetch_assoc($res)): 
                    ?>
                    <tr class="row-item">
                        <td><strong><?= $row['nama_produk'] ?></strong></td>
                        <td><span class="kat-label"><?= $row['kategori'] ?? 'Krupuk' ?></span></td>
                        <td><span class="price-tag">Rp <?= number_format($row['harga_satuan'], 0, ',', '.') ?></span></td>
                        <td><span class="stok-val"><?= $row['stok'] ?></span> <small>Unit</small></td>
                        <td style="text-align: center;">
                            <a href="edit_produk.php?id=<?= $row['id_produk'] ?>" class="btn-action">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Produk Baru</h3>
                <label for="modal-trigger"><i class="fa-solid fa-xmark"></i></label>
            </div>
            <div class="modal-body">
                <form action="proses_stok.php" method="POST">
                    <div class="input-box full">
                        <label>Nama Produk</label>
                        <input type="text" name="nama_produk" placeholder="Masukkan nama krupuk..." required />
                    </div>
                    <div class="form-grid">
                        <div class="input-box">
                            <label>Stok</label>
                            <input type="number" name="stok" required />
                        </div>
                        <div class="input-box">
                            <label>Kategori</label>
                            <input type="text" name="kategori" placeholder="Ikan/Udang" />
                        </div>
                    </div>
                    <div class="input-box full">
                        <label>Harga Jual (Rp)</label>
                        <input type="number" name="harga_jual" required />
                    </div>
                    <div class="modal-footer">
                        <label for="modal-trigger" class="btn-cancel">Batal</label>
                        <button type="submit" name="save" class="btn-save">Simpan Produk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>