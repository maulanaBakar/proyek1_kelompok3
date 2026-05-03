<?php 
session_start();
include 'koneksi.php'; 

if($_SESSION['status'] != "login"){
    header("location:login.php");
    exit; 
}

$hari_ini = date('Y-m-d');

// 1. Ambil Data Nama Toko
$q_toko = mysqli_query($koneksi, "SELECT nama_toko FROM pengaturan_toko WHERE id=1");
$d_toko = mysqli_fetch_assoc($q_toko);
$nama_toko = $d_toko['nama_toko'] ?? "2 PAKSI";

// 2. KARTU: Pendapatan Hari Ini
$q_pendapatan = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE DATE(tanggal_transaksi) = '$hari_ini'");
$d_pendapatan = mysqli_fetch_assoc($q_pendapatan);
$pendapatan_hari_ini = $d_pendapatan['total'] ?? 0;

// 3. KARTU: Total Transaksi Hari Ini
$q_transaksi = mysqli_query($koneksi, "SELECT COUNT(id_transaksi) as jumlah FROM transaksi WHERE DATE(tanggal_transaksi) = '$hari_ini'");
$d_transaksi = mysqli_fetch_assoc($q_transaksi);
$total_transaksi = $d_transaksi['jumlah'] ?? 0;

// 4. KARTU: Stok Menipis (Misal di bawah 10 pcs)
$q_stok_low = mysqli_query($koneksi, "SELECT COUNT(*) as jml FROM produk WHERE stok <= 10");
$d_stok_low = mysqli_fetch_assoc($q_stok_low);
$total_stok_low = $d_stok_low['jml'] ?? 0;

// 5. KARTU: Total Produk
$q_varian = mysqli_query($koneksi, "SELECT COUNT(id_produk) as total_produk FROM produk");
$d_varian = mysqli_fetch_assoc($q_varian);
$total_varian = $d_varian['total_produk'] ?? 0;

// 6. GRAFIK: Cari nilai tertinggi untuk skala bar
$q_max = mysqli_query($koneksi, "SELECT SUM(jumlah_produk) as max_jual FROM detail_transaksi GROUP BY id_produk ORDER BY max_jual DESC LIMIT 1");
$d_max = mysqli_fetch_assoc($q_max);
$max_terjual = $d_max['max_jual'] ?? 1;

?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - <?= $nama_toko ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* Tambahan Style untuk Grid Baru */
        .dashboard-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        /* Kartu Info Modern */
        .baris-kotak {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .kotak-info {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 5px solid var(--cokelat-muda, #d4a373);
        }
        .kotak-info .ikon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5em;
            background: #fdf8f3;
            color: var(--cokelat-muda, #d4a373);
        }
        .kotak-info.warning { border-left-color: #e74c3c; }
        .kotak-info.warning .ikon { color: #e74c3c; background: #fdf2f2; }

        /* Tabel Sederhana */
        .tabel-mini {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .tabel-mini th {
            text-align: left;
            font-size: 0.8em;
            color: #888;
            padding: 10px 5px;
            border-bottom: 1px solid #eee;
        }
        .tabel-mini td {
            padding: 12px 5px;
            border-bottom: 1px solid #f9f9f9;
            font-size: 0.9em;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 0.75em;
            font-weight: 600;
        }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-success { background: #d4edda; color: #155724; }

        @media (max-width: 992px) {
            .dashboard-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <input type="checkbox" id="check-menu">

    <div class="bar-atas-mobile">
        <div class="nama-toko"><?= $nama_toko ?></div>
        <label for="check-menu" class="tombol-buka"><i class="fa-solid fa-bars"></i></label>
    </div>

    <aside class="menu-samping">
        <div class="bagian-atas">
            <div class="judul-logo"><?= $nama_toko ?></div>
            <nav class="daftar-menu">
                <a href="dashboard.php" class="link-menu aktif"><i class="fa-solid fa-house"></i> Beranda</a>
                <a href="kasir.php" class="link-menu"><i class="fa-solid fa-cash-register"></i> Kasir</a>
                <a href="stok.php" class="link-menu"><i class="fa-solid fa-box"></i> Stok Barang</a>
                <a href="buku_kas.php" class="link-menu"><i class="fa-solid fa-wallet"></i> Buku Kas</a>
                <a href="laporan.php" class="link-menu"><i class="fa-solid fa-file-lines"></i> Laporan</a>
                <a href="pengaturan.php" class="link-menu"><i class="fa-solid fa-gear"></i> Pengaturan</a>
            </nav>
        </div>
        <div class="bagian-bawah">
            <a href="logout.php" class="link-menu keluar"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a>
        </div>
    </aside>

    <main class="isi-halaman">
        <header class="judul-halaman">
            <h1>Ringkasan Toko Hari Ini</h1>
            <p style="color: #888; font-size: 0.9em;"><?= date('l, d F Y') ?></p>
        </header>

        <div class="baris-kotak">
            <div class="kotak-info">
                <div class="ikon"><i class="fa-solid fa-wallet"></i></div>
                <div>
                    <div class="label-kecil">PENDAPATAN</div>
                    <div class="angka-besar" style="font-size: 1.2em;">Rp <?= number_format($pendapatan_hari_ini, 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="kotak-info">
                <div class="ikon"><i class="fa-solid fa-cart-shopping"></i></div>
                <div>
                    <div class="label-kecil">TRANSAKSI</div>
                    <div class="angka-besar"><?= $total_transaksi ?> <span>Pesanan</span></div>
                </div>
            </div>
            <div class="kotak-info <?= ($total_stok_low > 0) ? 'warning' : '' ?>">
                <div class="ikon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div>
                    <div class="label-kecil">STOK MENIPIS</div>
                    <div class="angka-besar"><?= $total_stok_low ?> <span>Produk</span></div>
                </div>
            </div>
            <div class="kotak-info">
                <div class="ikon"><i class="fa-solid fa-box"></i></div>
                <div>
                    <div class="label-kecil">TOTAL VARIAN</div>
                    <div class="angka-besar"><?= $total_varian ?> <span>Jenis</span></div>
                </div>
            </div>
        </div>

        <div class="dashboard-container">
            
            <div class="kolom-kiri">
                <div class="kotak-putih" style="margin-bottom: 20px;">
                    <h3 class="judul-sub">Grafik Unit Terjual</h3>
                    <div class="wadah-grafik">
                        <?php
                        $query_grafik = mysqli_query($koneksi, "SELECT p.nama_produk, SUM(d.jumlah_produk) as total_terjual FROM detail_transaksi d JOIN produk p ON d.id_produk = p.id_produk GROUP BY d.id_produk ORDER BY total_terjual DESC LIMIT 5");
                        if(mysqli_num_rows($query_grafik) > 0) {
                            while($g = mysqli_fetch_assoc($query_grafik)):
                                $persen = ($g['total_terjual'] / $max_terjual) * 100; 
                        ?>
                        <div class="item-grafik">
                            <div class="teks-grafik"><span><?= htmlspecialchars($g['nama_produk']) ?></span> <span><?= $g['total_terjual'] ?> Unit</span></div>
                            <div class="jalur-bar"><div class="isi-bar" style="width: <?= $persen ?>%;"></div></div>
                        </div>
                        <?php endwhile; } else { echo "<p style='text-align:center; color:#888;'>Belum ada data.</p>"; } ?>
                    </div>
                </div>

                <div class="kotak-putih">
                    <h3 class="judul-sub">Transaksi Terakhir</h3>
                    <table class="tabel-mini">
                        <thead>
                            <tr>
                                <th>WAKTU</th>
                                <th>NO. STRUK</th>
                                <th>TOTAL</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q_recent = mysqli_query($koneksi, "SELECT * FROM transaksi ORDER BY tanggal_transaksi DESC LIMIT 5");
                            while($r = mysqli_fetch_assoc($q_recent)):
                            ?>
                            <tr>
                                <td><?= date('H:i', strtotime($r['tanggal_transaksi'])) ?></td>
                                <td>#<?= $r['id_transaksi'] ?></td>
                                <td>Rp <?= number_format($r['total_pendapatan'], 0, ',', '.') ?></td>
                                <td><span class="badge badge-success">Selesai</span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="kolom-kanan">
                <div class="kotak-putih" style="margin-bottom: 20px; border-top: 4px solid #e74c3c;">
                    <h3 class="judul-sub">Peringatan Stok ⚠️</h3>
                    <table class="tabel-mini">
                        <thead>
                            <tr>
                                <th>PRODUK</th>
                                <th>SISA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q_list_stok = mysqli_query($koneksi, "SELECT nama_produk, stok FROM produk WHERE stok <= 10 ORDER BY stok ASC LIMIT 5");
                            if(mysqli_num_rows($q_list_stok) > 0) {
                                while($ls = mysqli_fetch_assoc($q_list_stok)):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($ls['nama_produk']) ?></td>
                                <td><span class="badge badge-danger"><?= $ls['stok'] ?> Pcs</span></td>
                            </tr>
                            <?php endwhile; } else { echo "<tr><td colspan='2' style='text-align:center; color:green;'>Stok aman semua ✅</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>

                <div class="kotak-putih">
                    <h3 class="judul-sub">Produk Terlaris 🏆</h3>
                    <?php
                    $q_laris = mysqli_query($koneksi, "SELECT p.nama_produk, SUM(d.jumlah_produk) as total FROM detail_transaksi d JOIN produk p ON d.id_produk = p.id_produk GROUP BY d.id_produk ORDER BY total DESC LIMIT 3");
                    $rank = 1;
                    while($l = mysqli_fetch_assoc($q_laris)):
                    ?>
                    <div class="item-produk" style="padding: 10px 0;">
                        <div class="info-produk">
                            <h4 style="margin:0; font-size:0.9em;"><?= htmlspecialchars($l['nama_produk']) ?></h4>
                            <p style="margin:0; font-size:0.8em; color:#888;"><?= $l['total'] ?> Unit Terjual</p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

        </div>
    </main>

</body>
</html>