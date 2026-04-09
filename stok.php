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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2 Paksi | Stok Barang</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stok.css">
</head>
<body>

    <input type="checkbox" id="check-menu">

    <div class="bar-atas-mobile">
        <div class="nama-toko">2 PAKSI</div>
        <label for="check-menu" class="tombol-buka">
            <i class="fa-solid fa-bars"></i>
        </label>
    </div>

    <aside class="sidebar">
        <div class="sidebar-atas">
            <div class="logo">2 PAKSI</div>
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link">
                    <i class="fa-solid fa-house"></i> Beranda
                </a>
                <a href="kasir.php" class="nav-link">
                    <i class="fa-solid fa-cash-register"></i> Kasir
                </a>
                <a href="stok.php" class="nav-link aktif">
                    <i class="fa-solid fa-box"></i> Stok Barang
                </a>
                <a href="laporan.php" class="nav-link">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Laporan
                </a>
            </nav>
        </div>
        <div class="sidebar-bawah">
            <a href="logout.php" class="nav-link keluar">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar
            </a>
        </div>
    </aside>

    <main class="content">
        <header class="header-halaman">
            <div class="judul">
                <h1>Stok Barang</h1>
                <p>Manajemen ketersediaan produk produksi</p>
            </div>
            <a href="#" class="btn-tambah">
                <i class="fa-solid fa-plus"></i> Tambah Produk
            </a>
        </header>

        <section class="box-tabel">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>PRODUK</th>
                            <th>KATEGORI</th>
                            <th>HARGA JUAL</th>
                            <th>STOK SISA</th>
                            <th style="text-align: center;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="baris-produk">
                            <td>
                                <div class="produk-info">
                                    <strong>Krupuk Ikan Tenggiri</strong>
                                    <span>#PRD-001</span>
                                </div>
                            </td>
                            <td><span class="tag-kategori">Krupuk</span></td>
                            <td><span class="harga">Rp 15.000</span></td>
                            <td>
                                <div class="stok-angka">
                                    <strong>45</strong> <small>Pcs</small>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <div class="dropdown-css">
                                    <button class="titik-tiga"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    <div class="menu-aksi">
                                        <a href="#"><i class="fa-solid fa-pen"></i> Edit</a>
                                        <a href="#" class="hapus"><i class="fa-solid fa-trash"></i> Hapus</a>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <tr class="baris-produk stok-kritis">
                            <td>
                                <div class="produk-info">
                                    <strong>Krupuk Udang Spesial</strong>
                                    <span>#PRD-002</span>
                                </div>
                            </td>
                            <td><span class="tag-kategori">Krupuk</span></td>
                            <td><span class="harga">Rp 12.000</span></td>
                            <td>
                                <div class="stok-angka">
                                    <strong>3</strong> <small>Pcs</small>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <div class="dropdown-css">
                                    <button class="titik-tiga"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    <div class="menu-aksi">
                                        <a href="#">Edit</a>
                                        <a href="#" class="hapus">Hapus</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

</body>
</html>