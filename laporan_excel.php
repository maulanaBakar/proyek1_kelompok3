<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');
 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
if ($_SESSION['status'] != "login") {
    header("location:login.php?pesan=belum_login");
    exit();
}
 
$hari_ini  = date('Y-m-d');
$bulan_ini = date('m');
$tahun_ini = date('Y');
$view      = $_GET['view'] ?? 'hari';
 
/* Statistik */
$q_hari = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE DATE(tanggal_transaksi)='$hari_ini'");
$total_hari = mysqli_fetch_assoc($q_hari)['total'] ?? 0;
 
$q_bulan = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE MONTH(tanggal_transaksi)='$bulan_ini' AND YEAR(tanggal_transaksi)='$tahun_ini'");
$total_bulan = mysqli_fetch_assoc($q_bulan)['total'] ?? 0;
 
$q_tahun = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE YEAR(tanggal_transaksi)='$tahun_ini'");
$total_tahun = mysqli_fetch_assoc($q_tahun)['total'] ?? 0;
 
/* View */
if ($view == 'bulan') {
    $title = "Rekap Penjualan Bulanan";
    $display_total = $total_bulan;
    $sql_tabel = "SELECT MONTH(tanggal_transaksi) as bln, SUM(total_pendapatan) as total, COUNT(*) as jml
                  FROM transaksi
                  WHERE YEAR(tanggal_transaksi)='$tahun_ini'
                  GROUP BY MONTH(tanggal_transaksi)
                  ORDER BY bln DESC";
} elseif ($view == 'tahun') {
    $title = "Rekap Penjualan Tahunan";
    $display_total = $total_tahun;
    $sql_tabel = "SELECT YEAR(tanggal_transaksi) as thn, SUM(total_pendapatan) as total, COUNT(*) as jml
                  FROM transaksi
                  GROUP BY YEAR(tanggal_transaksi)
                  ORDER BY thn DESC";
} else {
    $title = "Riwayat Transaksi";
    $display_total = $total_hari;
    $sql_tabel = "SELECT * FROM transaksi ORDER BY tanggal_transaksi DESC LIMIT 50";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>2 Paksi | Laporan</title>
 
<link rel="stylesheet" href="laporan.css">
<link rel="stylesheet" href="dashboard.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
 
<style>
/* Tombol Download */
.download-group {
    margin: 6px 0 10px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.download-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #8a9bb5;
    margin: 0 0 2px;
    padding-left: 2px;
}
.btn-dl {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: opacity .15s, transform .1s;
}
.btn-dl:hover         { opacity: .88; transform: translateX(2px); }
.btn-dl-pdf           { background: #e53935; color: #fff; }
.btn-dl-xls           { background: #1e7e34; color: #fff; }
</style>
</head>
<body>
 
<!-- MOBILE HEADER -->
<div class="bar-atas-mobile">
    <div class="nama-toko">2 PAKSI</div>
    <label for="check-menu" class="tombol-buka">
        <i class="fa-solid fa-bars"></i>
    </label>
</div>
 
<!-- SIDEBAR -->
<aside class="menu-samping">
    <div class="bagian-atas">
        <div class="judul-logo">2 PAKSI</div>
        <nav class="daftar-menu">
            <a href="dashboard.php" class="link-menu">
                <i class="fa-solid fa-house"></i> Beranda
            </a>
            <a href="kasir.php" class="link-menu">
                <i class="fa-solid fa-cash-register"></i> Kasir
            </a>
            <a href="stok.php" class="link-menu">
                <i class="fa-solid fa-box"></i> Stok Barang
            </a>
            <a href="laporan.php" class="link-menu aktif">
                <i class="fa-solid fa-file-lines"></i> Laporan
            </a>
            <a href="pengaturan.php" class="link-menu">
                <i class="fa-solid fa-gear"></i> <span>Pengaturan</span>
            </a>
        </nav>
    </div>
 
    <div class="bagian-bawah">
        <a href="logout.php" class="link-menu keluar">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar
        </a>
    </div>
</aside>
 
 
 
<!-- CONTENT -->
<main class="content">
    <div class="header">
        <h1>Laporan Keuangan</h1>
    </div>
 
    <div class="stats-container">
        <a href="?view=hari" class="card-stat <?= $view=='hari' ? 'active' : '' ?>">
            <p>Per Hari</p>
            <h3>Rp <?= number_format($total_hari,0,',','.') ?></h3>
        </a>
 
        <a href="?view=bulan" class="card-stat <?= $view=='bulan' ? 'active' : '' ?>">
            <p>Per Bulan</p>
            <h3>Rp <?= number_format($total_bulan,0,',','.') ?></h3>
        </a>
 
        <a href="?view=tahun" class="card-stat <?= $view=='tahun' ? 'active' : '' ?>">
            <p>Per Tahun</p>
            <h3>Rp <?= number_format($total_tahun,0,',','.') ?></h3>
        </a>

        <!-- TOMBOL DOWNLOAD -->
            <div class="download-group">
                <p class="download-label">Download Laporan</p>
                <a href="laporan_pdf.php?view=<?= $view ?>" target="_blank" class="btn-dl btn-dl-pdf">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="laporan_excel.php?view=<?= $view ?>" class="btn-dl btn-dl-xls">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
            </div>
    </div>
 
    <div class="report-card">
        <div class="table-header">
            <h3><?= $title ?></h3>
            <div class="total-box">
                Total Pendapatan
                <span>Rp <?= number_format($display_total,0,',','.') ?></span>
            </div>
        </div>
 
        <table>
            <thead>
            <?php if ($view == 'bulan' || $view == 'tahun') { ?>
                <tr>
                    <th>Periode</th>
                    <th>Jumlah TRX</th>
                    <th style="text-align:right;">Pendapatan</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            <?php } else { ?>
                <tr>
                    <th>ID</th>
                    <th>Waktu</th>
                    <th>Status</th>
                    <th style="text-align:right;">Jumlah</th>
                </tr>
            <?php } ?>
            </thead>
 
            <tbody>
            <?php
            $res = mysqli_query($koneksi, $sql_tabel);
 
            while ($row = mysqli_fetch_assoc($res)) {
 
                if ($view == 'bulan') {
                    $nm_bulan = date('F', mktime(0,0,0,$row['bln'],10));
 
                    echo "
                    <tr>
                        <td>$nm_bulan</td>
                        <td>{$row['jml']} TRX</td>
                        <td style='text-align:right' class='price'>Rp ".number_format($row['total'],0,',','.')."</td>
                        <td style='text-align:center'><a href='?view=hari' class='badge-link'>Detail</a></td>
                    </tr>";
                }
 
                elseif ($view == 'tahun') {
                    echo "
                    <tr>
                        <td>{$row['thn']}</td>
                        <td>{$row['jml']} TRX</td>
                        <td style='text-align:right' class='price'>Rp ".number_format($row['total'],0,',','.')."</td>
                        <td style='text-align:center'><a href='?view=bulan' class='badge-link'>Bulanan</a></td>
                    </tr>";
                }
 
                else {
                    echo "
                    <tr>
                        <td>
                            <button onclick='showDetail({$row['id_transaksi']})' class='btn-trx'>
                                #TRX_{$row['id_transaksi']}
                            </button>
                        </td>
                        <td>".date('d M Y H:i', strtotime($row['tanggal_transaksi']))."</td>
                        <td><span style='color:#27ae60;font-weight:700;'>Berhasil</span></td>
                        <td style='text-align:right' class='price'>
                            Rp ".number_format($row['total_pendapatan'],0,',','.')."
                        </td>
                    </tr>";
                }
            }
            ?>
            </tbody>
        </table>
    </div>
</main>
 
<!-- MODAL -->
<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3 class="modal-title">Rincian Produk</h3>
        <div id="modal-body">Memuat data...</div>
    </div>
</div>
 
<script>
function showDetail(id) {
    var modal = document.getElementById("myModal");
    var modalBody = document.getElementById("modal-body");
    
    modal.style.display = "block";
    modalBody.innerHTML = "Memuat data...";
 
    fetch('get_detail.php?id=' + id)
        .then(response => response.text())
        .then(data => {
            modalBody.innerHTML = data;
        })
        .catch(error => {
            modalBody.innerHTML = "Gagal memuat data.";
        });
}
 
var modal = document.getElementById("myModal");
var span = document.getElementsByClassName("close")[0];
 
span.onclick = function() {
    modal.style.display = "none";
}
 
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>
 
<script src="laporan.js"></script>
</body>
</html>