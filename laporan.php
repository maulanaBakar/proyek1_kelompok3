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
/* Styling Form Download Spesifik */

/* Container Utama Download */

.download-group {
    margin: 10px 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.download-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #000000;
    margin-bottom: 4px;
}

/* Box Filter Tanggal */
.download-filter-box {
    background: #4a3e3d;
    padding: 15px;
    border-radius: 12px;
    border: 1px solid #f3f4f5;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.input-row {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
}

.input-field {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.input-field label {
    font-size: 11px;
    font-weight: 700;
    color: #ffffff;
}

.input-field input {
    padding: 10px;
    border: 1.5px solid #dde3f0;
    border-radius: 8px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 13px;
    color: #1a1a2e;
    outline: none;
    transition: border-color 0.2s;
}

.input-field input:focus {
    border-color: #4a4a4a;
}

/* Tombol Download */
.btn-download-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.btn-dl {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s;
    color: #fff !important; /* Paksa warna teks putih */
}

.btn-dl:hover {
    filter: brightness(1.1);
    transform: translateY(-1px);
}

.btn-dl-pdf { background: #bc987e; } /* PDF */
.btn-dl-xls { background: #bc987e; } /* Excel */

.btn-dl i {
    font-size: 14px;
}

</style>
</head>
 
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
    <p class="download-label">Download Khusus Periode</p>
    
    <div class="download-filter-box">
        <div class="input-row">
            <div class="input-field">
                <label>Mulai</label>
                <input type="date" id="tgl_mulai" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="input-field">
                <label>Sampai</label>
                <input type="date" id="tgl_selesai" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div class="btn-download-wrapper">
            <a href="#" onclick="generateDownload('pdf')" class="btn-dl btn-dl-pdf" id="link-pdf">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
            <a href="#" onclick="generateDownload('excel')" class="btn-dl btn-dl-xls" id="link-excel">
                <i class="fa-solid fa-file-excel"></i> Excel
            </a>
        </div>
    </div>
    
    <p class="download-label" style="margin-top:10px;">Cepat (Sesuai View)</p>
    <a href="laporan_pdf.php?view=<?= $view ?>" target="_blank" style="font-size:11px; color:#1a73e8; text-decoration:none; font-weight:700;">
        <i class="fa-solid fa-print"></i> Download <?= ucfirst($view) ?> Ini
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

function toggleDownload(event) {
    event.stopPropagation(); // Mencegah event klik tembus ke window
    document.getElementById("myDropdown").classList.toggle("show");
}

window.onclick = function(event) {
    var dropdown = document.getElementById("myDropdown");
    // Jika user klik di luar tombol dan dropdown sedang terbuka, maka tutup
    if (!event.target.matches('.btn-main-dl') && !event.target.closest('.btn-main-dl')) {
        if (dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    }
}

function generateDownload(type) {
    const start = document.getElementById('tgl_mulai').value;
    const end = document.getElementById('tgl_selesai').value;
    
    if (!start || !end) {
        alert("Silakan pilih tanggal terlebih dahulu");
        return;
    }

    let url = "";
    if (type === 'pdf') {
        url = `laporan_pdf.php?view=custom&start_date=${start}&end_date=${end}`;
        window.open(url, '_blank'); // Buka PDF di tab baru
    } else {
        url = `laporan_excel.php?view=custom&start_date=${start}&end_date=${end}`;
        window.location.href = url; // Download Excel langsung
    }
}

</script>

<script src="laporan.js"></script>
</body>
</html>
 