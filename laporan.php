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

// --- LOGIKA FILER FASE 4 ---
$view      = $_GET['view'] ?? 'hari';
$tgl_mulai   = $_GET['start_date'] ?? $hari_ini; 
$tgl_selesai = $_GET['end_date'] ?? $hari_ini;
 
/* Subquery HPP */
$sql_hpp = "(
    SELECT d.id_transaksi, SUM(d.jumlah_produk * IF(p.jenis_produk = 'Luar', p.modal, p.hpp)) as total_hpp
    FROM detail_transaksi d
    JOIN produk p ON d.id_produk = p.id_produk
    GROUP BY d.id_transaksi
)";

/* BIAYA OPERASIONAL (DUMMY) */
$ops_hari  = 0; 
$ops_bulan = 0;
$ops_tahun = 0;

/* Subquery Kerugian */
$q_rugi_hari = mysqli_query($koneksi, "SELECT SUM(nilai_kerugian) as rugi FROM riwayat_kerugian WHERE tanggal = '$hari_ini'");
$rugi_hari = mysqli_fetch_assoc($q_rugi_hari)['rugi'] ?? 0;

$q_rugi_bulan = mysqli_query($koneksi, "SELECT SUM(nilai_kerugian) as rugi FROM riwayat_kerugian WHERE MONTH(tanggal) = '$bulan_ini' AND YEAR(tanggal) = '$tahun_ini'");
$rugi_bulan = mysqli_fetch_assoc($q_rugi_bulan)['rugi'] ?? 0;

$q_rugi_tahun = mysqli_query($koneksi, "SELECT SUM(nilai_kerugian) as rugi FROM riwayat_kerugian WHERE YEAR(tanggal) = '$tahun_ini'");
$rugi_tahun = mysqli_fetch_assoc($q_rugi_tahun)['rugi'] ?? 0;

/* Statistik Ringkasan Atas */
$q_hari = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE DATE(tanggal_transaksi)='$hari_ini'");
$total_hari = mysqli_fetch_assoc($q_hari)['total'] ?? 0;

$q_bulan = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE MONTH(tanggal_transaksi)='$bulan_ini' AND YEAR(tanggal_transaksi)='$tahun_ini'");
$total_bulan = mysqli_fetch_assoc($q_bulan)['total'] ?? 0;

$q_tahun = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE YEAR(tanggal_transaksi)='$tahun_ini'");
$total_tahun = mysqli_fetch_assoc($q_tahun)['total'] ?? 0;

/* Penentuan SQL Tabel & Title */
if ($view == 'bulan') {
    $title = "Rekap Penjualan Bulanan ($tahun_ini)";
    $display_total = $total_bulan;
    $sql_tabel = "SELECT MONTH(t.tanggal_transaksi) as bln, COUNT(t.id_transaksi) as jml, SUM(t.total_pendapatan) as total, SUM(hpp_trx.total_hpp) as total_hpp
                  FROM transaksi t LEFT JOIN $sql_hpp hpp_trx ON t.id_transaksi = hpp_trx.id_transaksi
                  WHERE YEAR(t.tanggal_transaksi)='$tahun_ini' GROUP BY MONTH(t.tanggal_transaksi) ORDER BY bln DESC";
} elseif ($view == 'tahun') {
    $title = "Rekap Penjualan Tahunan";
    $display_total = $total_tahun;
    $sql_tabel = "SELECT YEAR(t.tanggal_transaksi) as thn, COUNT(t.id_transaksi) as jml, SUM(t.total_pendapatan) as total, SUM(hpp_trx.total_hpp) as total_hpp
                  FROM transaksi t LEFT JOIN $sql_hpp hpp_trx ON t.id_transaksi = hpp_trx.id_transaksi
                  GROUP BY YEAR(t.tanggal_transaksi) ORDER BY thn DESC";
} elseif ($view == 'custom') {
    $title = "Laporan: " . date('d/m/y', strtotime($tgl_mulai)) . " - " . date('d/m/y', strtotime($tgl_selesai));
    $q_custom = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE DATE(tanggal_transaksi) BETWEEN '$tgl_mulai' AND '$tgl_selesai'");
    $display_total = mysqli_fetch_assoc($q_custom)['total'] ?? 0;
    $sql_tabel = "SELECT t.*, hpp_trx.total_hpp FROM transaksi t LEFT JOIN $sql_hpp hpp_trx ON t.id_transaksi = hpp_trx.id_transaksi 
                  WHERE DATE(t.tanggal_transaksi) BETWEEN '$tgl_mulai' AND '$tgl_selesai' ORDER BY t.tanggal_transaksi DESC";
} else {
    $title = "Riwayat Transaksi Hari Ini";
    $display_total = $total_hari;
    $sql_tabel = "SELECT t.*, hpp_trx.total_hpp FROM transaksi t LEFT JOIN $sql_hpp hpp_trx ON t.id_transaksi = hpp_trx.id_transaksi 
                  WHERE DATE(t.tanggal_transaksi) = '$hari_ini' ORDER BY t.tanggal_transaksi DESC";
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
</head>
<body>

<aside class="menu-samping">
    <div class="bagian-atas">
        <div class="judul-logo">2 PAKSI</div>
        <nav class="daftar-menu">
            <a href="dashboard.php" class="link-menu"><i class="fa-solid fa-house"></i> Beranda</a>
            <a href="kasir.php" class="link-menu"><i class="fa-solid fa-cash-register"></i> Kasir</a>
            <a href="stok.php" class="link-menu"><i class="fa-solid fa-box"></i> Stok Barang</a>
            <a href="buku_kas.php" class="link-menu"><i class="fa-solid fa-wallet"></i> Buku Kas</a>
            <a href="laporan.php" class="link-menu aktif"><i class="fa-solid fa-file-lines"></i> Laporan</a>
        </nav>
    </div>
    <div class="bagian-bawah">
        <a href="logout.php" class="link-menu keluar"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a>
    </div>
</aside>

<main class="content">
    <div class="header"><h1>Laporan Keuangan</h1></div>

    <div class="stats-container">
        <a href="?view=hari" class="card-stat <?= $view=='hari' ? 'active' : '' ?>">
            <p>Hari Ini</p>
            <h3>Rp <?= number_format($total_hari,0,',','.') ?></h3>
        </a>
        <a href="?view=bulan" class="card-stat <?= $view=='bulan' ? 'active' : '' ?>">
            <p>Bulan Ini</p>
            <h3>Rp <?= number_format($total_bulan,0,',','.') ?></h3>
        </a>
        <a href="?view=tahun" class="card-stat <?= $view=='tahun' ? 'active' : '' ?>">
            <p>Tahun Ini</p>
            <h3>Rp <?= number_format($total_tahun,0,',','.') ?></h3>
        </a>

        <div class="download-group">
            <p class="download-label">Filter Periode</p>
            <form action="" method="GET" class="download-filter-box">
                <input type="hidden" name="view" value="custom">
                <div class="input-row">
                    <div class="input-field">
                        <label>Mulai</label>
                        <input type="date" name="start_date" id="tgl_mulai" value="<?= $tgl_mulai ?>">
                    </div>
                    <div class="input-field">
                        <label>Sampai</label>
                        <input type="date" name="end_date" id="tgl_selesai" value="<?= $tgl_selesai ?>">
                    </div>
                </div>
                <div class="btn-download-wrapper">
                    <button type="submit" class="btn-dl" style="background:#3498db; border:none; color:white; cursor:pointer;"><i class="fa-solid fa-filter"></i> Filter</button>
                    <button type="button" onclick="generateDownload('pdf')" class="btn-dl" style="background:#e74c3c; border:none; color:white; cursor:pointer;"><i class="fa-solid fa-file-pdf"></i> PDF</button>
                </div>
            </form>
        </div>
    </div>

    <div class="report-card">
        <div class="table-header">
            <h3><i class="fa-solid fa-chart-line"></i> <?= $title ?></h3>
            <div class="total-box">Total: <span>Rp <?= number_format($display_total,0,',','.') ?></span></div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <?php if ($view == 'bulan' || $view == 'tahun'): ?>
                        <tr><th>Periode</th><th>Transaksi</th><th>Pendapatan</th><th>Laba Kotor</th><th>Aksi</th></tr>
                    <?php else: ?>
                        <tr><th>ID TRX</th><th>Waktu</th><th>Pendapatan</th><th>Laba Kotor</th></tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                <?php
                $res = mysqli_query($koneksi, $sql_tabel);
                if(mysqli_num_rows($res) > 0) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $pendapatan = ($view == 'bulan' || $view == 'tahun') ? $row['total'] : $row['total_pendapatan'];
                        $laba_kotor = $pendapatan - ($row['total_hpp'] ?? 0);

                        if ($view == 'bulan') {
                            $nm_bulan = date('F', mktime(0,0,0,$row['bln'],10));
                            $link = "?view=custom&start_date=$tahun_ini-".sprintf('%02d',$row['bln'])."-01&end_date=$tahun_ini-".sprintf('%02d',$row['bln'])."-31";
                            echo "<tr><td><strong>$nm_bulan</strong></td><td>{$row['jml']} TRX</td><td>Rp ".number_format($pendapatan,0,',','.')."</td><td>Rp ".number_format($laba_kotor,0,',','.')."</td><td><a href='$link' class='badge-link'>Detail</a></td></tr>";
                        } elseif ($view == 'tahun') {
                            $link = "?view=custom&start_date={$row['thn']}-01-01&end_date={$row['thn']}-12-31";
                            echo "<tr><td><strong>{$row['thn']}</strong></td><td>{$row['jml']} TRX</td><td>Rp ".number_format($pendapatan,0,',','.')."</td><td>Rp ".number_format($laba_kotor,0,',','.')."</td><td><a href='$link' class='badge-link'>Lihat</a></td></tr>";
                        } else {
                            echo "<tr><td><button onclick='showDetail({$row['id_transaksi']})' class='btn-trx'>#TRX_{$row['id_transaksi']}</button></td><td>".date('d M Y H:i', strtotime($row['tanggal_transaksi']))."</td><td>Rp ".number_format($pendapatan,0,',','.')."</td><td>Rp ".number_format($laba_kotor,0,',','.')."</td></tr>";
                        }
                    }
                } else { echo "<tr><td colspan='5' style='text-align:center; padding:20px;'>Tidak ada data.</td></tr>"; }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3 class="modal-title">Rincian Produk</h3>
        <div id="modal-body">Memuat...</div>
    </div>
</div>

<script>
function generateDownload(type) {
    const start = document.getElementById('tgl_mulai').value;
    const end = document.getElementById('tgl_selesai').value;
    if (!start || !end) { alert("Pilih tanggal!"); return; }
    window.open(`laporan_pdf.php?view=custom&start_date=${start}&end_date=${end}`, '_blank');
}
function showDetail(id) {
    const modal = document.getElementById("myModal");
    modal.style.display = "block";
    fetch('detailkasir.php?id=' + id).then(r => r.text()).then(data => { document.getElementById("modal-body").innerHTML = data; });
}
document.querySelector(".close").onclick = () => { document.getElementById("myModal").style.display = "none"; }
</script>
</body>
</html>