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

// 6. KARTU: Saldo Kas (Hitungan dari Buku Kas)
$m = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(nominal) as t FROM buku_kas WHERE jenis='Pemasukan'"))['t'] ?? 0;
$k = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(nominal) as t FROM buku_kas WHERE jenis='Pengeluaran'"))['t'] ?? 0;
$saldo_kasir = $m - $k;

// 7. GRAFIK: Cari nilai tertinggi untuk skala bar
$q_max = mysqli_query($koneksi, "SELECT SUM(jumlah_produk) as max_jual FROM detail_transaksi GROUP BY id_produk ORDER BY max_jual DESC LIMIT 1");
$d_max = mysqli_fetch_assoc($q_max);
$max_terjual = $d_max['max_jual'] ?? 1;

// ==========================================
// FASE 6: ASISTEN ANALITIK (RADAR STOK MATI)
// ==========================================
// Cari produk yg STOK > 0 tapi TIDAK PERNAH TERJUAL selama 14 HARI TERAKHIR
$q_stok_mati = mysqli_query($koneksi, "
    SELECT p.nama_produk, p.stok, MAX(t.tanggal_transaksi) as terakhir_terjual
    FROM produk p
    LEFT JOIN detail_transaksi d ON p.id_produk = d.id_produk
    LEFT JOIN transaksi t ON d.id_transaksi = t.id_transaksi
    WHERE p.stok > 0
    GROUP BY p.id_produk
    HAVING terakhir_terjual < DATE_SUB(CURDATE(), INTERVAL 14 DAY) OR terakhir_terjual IS NULL
    ORDER BY p.stok DESC
    LIMIT 3
");
$jumlah_stok_mati = mysqli_num_rows($q_stok_mati);
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - <?= $nama_toko ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .dashboard-container { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px; }
        .baris-kotak { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .kotak-info { background: white; padding: 15px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 12px; border-left: 5px solid #d4a373; }
        .kotak-info .ikon { width: 45px; height: 45px; border-radius: 10px; display: flex; justify-content: center; align-items: center; font-size: 1.2em; background: #fdf8f3; color: #d4a373; }
        
        /* Warna Khusus Saldo Kas */
        .kotak-info.saldo { border-left-color: #27ae60; }
        .kotak-info.saldo .ikon { color: #27ae60; background: #e8f8f5; }
        
        .kotak-info.warning { border-left-color: #e74c3c; }
        .kotak-info.warning .ikon { color: #e74c3c; background: #fdf2f2; }
        .label-kecil { font-size: 0.75em; color: #888; font-weight: 600; }
        .angka-besar { font-weight: 800; color: #2d2424; font-size: 1.1em; }
        .tabel-mini { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabel-mini th { text-align: left; font-size: 0.8em; color: #888; padding: 10px 5px; border-bottom: 1px solid #eee; }
        .tabel-mini td { padding: 12px 5px; border-bottom: 1px solid #f9f9f9; font-size: 0.9em; }
        .badge { padding: 4px 8px; border-radius: 5px; font-size: 0.75em; font-weight: 600; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-success { background: #d4edda; color: #155724; }
        @media (max-width: 992px) { .dashboard-container { grid-template-columns: 1fr; } }
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
            <div class="kotak-info saldo">
                <div class="ikon"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div>
                    <div class="label-kecil">SALDO KAS (SAAT INI)</div>
                    <div class="angka-besar">Rp <?= number_format($saldo_kasir, 0, ',', '.') ?></div>
                </div>
            </div>

            <div class="kotak-info">
                <div class="ikon"><i class="fa-solid fa-wallet"></i></div>
                <div>
                    <div class="label-kecil">OMSET HARI INI</div>
                    <div class="angka-besar">Rp <?= number_format($pendapatan_hari_ini, 0, ',', '.') ?></div>
                </div>
            </div>

            <div class="kotak-info">
                <div class="ikon"><i class="fa-solid fa-cart-shopping"></i></div>
                <div>
                    <div class="label-kecil">TRANSAKSI</div>
                    <div class="angka-besar"><?= $total_transaksi ?> <span>Nota</span></div>
                </div>
            </div>

            <div class="kotak-info <?= ($total_stok_low > 0) ? 'warning' : '' ?>">
                <div class="ikon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div>
                    <div class="label-kecil">STOK MENIPIS</div>
                    <div class="angka-besar"><?= $total_stok_low ?> <span>Produk</span></div>
                </div>
            </div>
        </div>

        <div style="background: linear-gradient(135deg, #fdfbf7 0%, #f4e8d8 100%); padding: 20px; border-radius: 15px; border: 2px dashed #d4a373; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <h3 style="margin-top: 0; color: #d35400; font-size: 1.1em; display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-robot"></i> Asisten Toko Pintar (Radar Stok Mati)
            </h3>
            <p style="font-size: 0.85em; color: #666; margin-bottom: 15px;">Mendeteksi uang modal yang mandek! Produk di bawah ini belum laku lebih dari 14 hari. Pertimbangkan untuk diobral.</p>
            
            <?php if($jumlah_stok_mati > 0): ?>
                <table class="tabel-mini" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <tbody>
                        <?php while($m = mysqli_fetch_assoc($q_stok_mati)): 
                            $tgl_jual = $m['terakhir_terjual'] ? date('d/m/Y', strtotime($m['terakhir_terjual'])) : 'Belum pernah laku';
                        ?>
                        <tr>
                            <td style="padding-left:15px; border-left: 4px solid #e74c3c;">
                                <strong><?= htmlspecialchars($m['nama_produk']) ?></strong><br>
                                <span style="font-size:0.8em; color:#888;"><i class="fa-regular fa-clock"></i> Terakhir laku: <?= $tgl_jual ?></span>
                            </td>
                            <td style="text-align:right; padding-right:15px;">
                                <span class="badge badge-danger">Nganggur <?= $m['stok'] ?> Pcs</span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="background: white; padding: 12px; border-radius: 8px; text-align: center; color: #27ae60; font-weight: 600; font-size: 0.9em; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <i class="fa-solid fa-check-circle"></i> Mantap! Perputaran stok cepat, tidak ada barang yang mengendap lama.
                </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-container">
            <div class="kolom-kiri">
                <div class="kotak-putih" style="margin-bottom: 20px; padding: 20px; background: white; border-radius: 15px;">
                    <h3 class="judul-sub">Grafik Unit Terjual</h3>
                    <div class="wadah-grafik">
                        <?php
                        $query_grafik = mysqli_query($koneksi, "SELECT p.nama_produk, SUM(d.jumlah_produk) as total_terjual FROM detail_transaksi d JOIN produk p ON d.id_produk = p.id_produk GROUP BY d.id_produk ORDER BY total_terjual DESC LIMIT 5");
                        if(mysqli_num_rows($query_grafik) > 0) {
                            while($g = mysqli_fetch_assoc($query_grafik)):
                                $persen = ($g['total_terjual'] / $max_terjual) * 100; 
                        ?>
                        <div class="item-grafik" style="margin-bottom:15px;">
                            <div class="teks-grafik" style="display:flex; justify-content:space-between; font-size:0.9em; margin-bottom:5px;">
                                <span><?= htmlspecialchars($g['nama_produk']) ?></span> 
                                <strong><?= $g['total_terjual'] ?> Unit</strong>
                            </div>
                            <div class="jalur-bar" style="background:#eee; height:8px; border-radius:10px; overflow:hidden;">
                                <div class="isi-bar" style="width: <?= $persen ?>%; background:#d4a373; height:100%;"></div>
                            </div>
                        </div>
                        <?php endwhile; } else { echo "<p style='text-align:center; color:#888;'>Belum ada data.</p>"; } ?>
                    </div>
                </div>

                <div class="kotak-putih" style="padding: 20px; background: white; border-radius: 15px;">
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
                <div class="kotak-putih" style="margin-bottom: 20px; padding: 20px; background: white; border-radius: 15px; border-top: 4px solid #e74c3c;">
                    <h3 class="judul-sub">Peringatan Stok </h3>
                    <table class="tabel-mini">
                        <tbody>
                            <?php
                            $q_list_stok = mysqli_query($koneksi, "SELECT nama_produk, stok FROM produk WHERE stok <= 10 ORDER BY stok ASC LIMIT 5");
                            if(mysqli_num_rows($q_list_stok) > 0) {
                                while($ls = mysqli_fetch_assoc($q_list_stok)):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($ls['nama_produk']) ?></td>
                                <td style="text-align:right;"><span class="badge badge-danger"><?= $ls['stok'] ?> Pcs</span></td>
                            </tr>
                            <?php endwhile; } else { echo "<tr><td colspan='2' style='text-align:center; color:green; padding:20px;'>Stok aman semua ✅</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>

                <div class="kotak-putih" style="padding: 20px; background: white; border-radius: 15px;">
                    <h3 class="judul-sub">Produk Terlaris </h3>
                    <?php
                    $q_laris = mysqli_query($koneksi, "SELECT p.nama_produk, SUM(d.jumlah_produk) as total FROM detail_transaksi d JOIN produk p ON d.id_produk = p.id_produk GROUP BY d.id_produk ORDER BY total DESC LIMIT 3");
                    while($l = mysqli_fetch_assoc($q_laris)):
                    ?>
                    <div style="border-bottom: 1px solid #eee; padding: 10px 0;">
                        <h4 style="margin:0; font-size:0.9em;"><?= htmlspecialchars($l['nama_produk']) ?></h4>
                        <p style="margin:0; font-size:0.8em; color:#888;"><?= $l['total'] ?> Unit Terjual</p>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </main>

</body>
</html>