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

/* Statistik Hari */
$q_hari = mysqli_query($koneksi, "
    SELECT SUM(t.total_pendapatan) as total, SUM(hpp_trx.total_hpp) as total_hpp
    FROM transaksi t
    LEFT JOIN $sql_hpp hpp_trx ON t.id_transaksi = hpp_trx.id_transaksi
    WHERE DATE(t.tanggal_transaksi)='$hari_ini'
");
$data_hari = mysqli_fetch_assoc($q_hari);
$total_hari = $data_hari['total'] ?? 0;
$hpp_hari   = $data_hari['total_hpp'] ?? 0;

$laba_kotor_hari  = $total_hari - $hpp_hari;
$laba_bersih_hari = $laba_kotor_hari - $ops_hari - $rugi_hari;
 
/* Statistik Bulan */
$q_bulan = mysqli_query($koneksi, "
    SELECT SUM(t.total_pendapatan) as total, SUM(hpp_trx.total_hpp) as total_hpp
    FROM transaksi t
    LEFT JOIN $sql_hpp hpp_trx ON t.id_transaksi = hpp_trx.id_transaksi
    WHERE MONTH(t.tanggal_transaksi)='$bulan_ini' AND YEAR(t.tanggal_transaksi)='$tahun_ini'
");
$data_bulan = mysqli_fetch_assoc($q_bulan);
$total_bulan = $data_bulan['total'] ?? 0;
$hpp_bulan   = $data_bulan['total_hpp'] ?? 0;

$laba_kotor_bulan  = $total_bulan - $hpp_bulan;
$laba_bersih_bulan = $laba_kotor_bulan - $ops_bulan - $rugi_bulan;
 
/* Statistik Tahun */
$q_tahun = mysqli_query($koneksi, "
    SELECT SUM(t.total_pendapatan) as total, SUM(hpp_trx.total_hpp) as total_hpp
    FROM transaksi t
    LEFT JOIN $sql_hpp hpp_trx ON t.id_transaksi = hpp_trx.id_transaksi
    WHERE YEAR(t.tanggal_transaksi)='$tahun_ini'
");
$data_tahun = mysqli_fetch_assoc($q_tahun);
$total_tahun = $data_tahun['total'] ?? 0;
$hpp_tahun   = $data_tahun['total_hpp'] ?? 0;

$laba_kotor_tahun  = $total_tahun - $hpp_tahun;
$laba_bersih_tahun = $laba_kotor_tahun - $ops_tahun - $rugi_tahun;
 
/* View Tabel (PERBAIKAN FILTER HARI INI) */
if ($view == 'bulan') {
    $title = "Rekap Penjualan Bulanan";
    $display_total = $total_bulan;
    $sql_tabel = "
        SELECT MONTH(t.tanggal_transaksi) as bln, COUNT(t.id_transaksi) as jml, 
               SUM(t.total_pendapatan) as total, SUM(hpp_trx.total_hpp) as total_hpp
        FROM transaksi t
        LEFT JOIN $sql_hpp hpp_trx ON t.id_transaksi = hpp_trx.id_transaksi
        WHERE YEAR(t.tanggal_transaksi)='$tahun_ini'
        GROUP BY MONTH(t.tanggal_transaksi)
        ORDER BY bln DESC";
} elseif ($view == 'tahun') {
    $title = "Rekap Penjualan Tahunan";
    $display_total = $total_tahun;
    $sql_tabel = "
        SELECT YEAR(t.tanggal_transaksi) as thn, COUNT(t.id_transaksi) as jml, 
               SUM(t.total_pendapatan) as total, SUM(hpp_trx.total_hpp) as total_hpp
        FROM transaksi t
        LEFT JOIN $sql_hpp hpp_trx ON t.id_transaksi = hpp_trx.id_transaksi
        GROUP BY YEAR(t.tanggal_transaksi)
        ORDER BY thn DESC";
} else {
    // PERBAIKAN: Menambahkan filter WHERE DATE(tanggal) = hari ini
    $title = "Riwayat Transaksi Hari Ini";
    $display_total = $total_hari;
    $sql_tabel = "
        SELECT t.*, hpp_trx.total_hpp 
        FROM transaksi t 
        LEFT JOIN $sql_hpp hpp_trx ON t.id_transaksi = hpp_trx.id_transaksi 
        WHERE DATE(t.tanggal_transaksi) = '$hari_ini'
        ORDER BY t.tanggal_transaksi DESC";
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
<div class="bar-atas-mobile">
    <div class="nama-toko">2 PAKSI</div>
    <label for="check-menu" class="tombol-buka"><i class="fa-solid fa-bars"></i></label>
</div>
 
<aside class="menu-samping">
    <div class="bagian-atas">
        <div class="judul-logo">2 PAKSI</div>
        <nav class="daftar-menu">
            <a href="dashboard.php" class="link-menu"><i class="fa-solid fa-house"></i> Beranda</a>
            <a href="kasir.php" class="link-menu"><i class="fa-solid fa-cash-register"></i> Kasir</a>
            <a href="stok.php" class="link-menu"><i class="fa-solid fa-box"></i> Stok Barang</a>
            <a href="buku_kas.php" class="link-menu"><i class="fa-solid fa-wallet"></i> Buku Kas</a>
            <a href="laporan.php" class="link-menu aktif"><i class="fa-solid fa-file-lines"></i> Laporan</a>
            <a href="pengaturan.php" class="link-menu"><i class="fa-solid fa-gear"></i> <span>Pengaturan</span></a>
        </nav>
    </div>
    <div class="bagian-bawah">
        <a href="logout.php" class="link-menu keluar"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a>
    </div>
</aside>

<main class="content">
    <div class="header">
        <h1>Laporan Keuangan</h1>
    </div>

    <div class="stats-container">
        <a href="?view=hari" class="card-stat <?= $view=='hari' ? 'active' : '' ?>">
            <p>Per Hari Ini</p>
            <h3>Rp <?= number_format($total_hari,0,',','.') ?></h3>
            <div class="laba-grup">
                <span class="laba-kotor-text">Laba Kotor: Rp <?= number_format($laba_kotor_hari,0,',','.') ?></span>
                <span class="laba-bersih-text">Laba Bersih: Rp <?= number_format($laba_bersih_hari,0,',','.') ?></span>
            </div>
        </a>

        <a href="?view=bulan" class="card-stat <?= $view=='bulan' ? 'active' : '' ?>">
            <p>Per Bulan Ini</p>
            <h3>Rp <?= number_format($total_bulan,0,',','.') ?></h3>
            <div class="laba-grup">
                <span class="laba-kotor-text">Laba Kotor: Rp <?= number_format($laba_kotor_bulan,0,',','.') ?></span>
                <span class="laba-bersih-text">Laba Bersih: Rp <?= number_format($laba_bersih_bulan,0,',','.') ?></span>
            </div>
        </a>

        <a href="?view=tahun" class="card-stat <?= $view=='tahun' ? 'active' : '' ?>">
            <p>Per Tahun Ini</p>
            <h3>Rp <?= number_format($total_tahun,0,',','.') ?></h3>
            <div class="laba-grup">
                <span class="laba-kotor-text">Laba Kotor: Rp <?= number_format($laba_kotor_tahun,0,',','.') ?></span>
                <span class="laba-bersih-text">Laba Bersih: Rp <?= number_format($laba_bersih_tahun,0,',','.') ?></span>
            </div>
        </a>

        <div class="download-group">
            <p class="download-label">Unduh Laporan Khusus</p>
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
                    <a href="#" onclick="generateDownload('pdf')" class="btn-dl" style="background: #e74c3c;"><i class="fa-regular fa-file-pdf" style="font-size:14px;"></i> Ekspor PDF</a>
                    <a href="#" onclick="generateDownload('excel')" class="btn-dl" style="background: #27ae60;"><i class="fa-regular fa-file-excel" style="font-size:14px;"></i> Ekspor Excel</a>
                </div>
            </div>
            
            <a href="laporan_pdf.php?view=<?= $view ?>" target="_blank" style="margin-top: 12px; font-size:12px; color:#fff; background:var(--primary); padding: 10px 12px; border-radius: 8px; text-decoration:none; font-weight:700; display:flex; align-items:center; justify-content:center; gap: 8px; transition: 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <i class="fa-solid fa-print"></i> Cetak Laporan <?= ucfirst($view) ?>
            </a>
        </div>
    </div>

    <div class="report-card">
        <div class="table-header">
            <div>
                <h3><i class="fa-solid fa-chart-simple" style="margin-right: 8px;"></i> <?= $title ?></h3>
                <small style="color:var(--text-muted); font-size:11px;">*Laba Bersih Dashboard telah dikurangi nominal total barang rusak.</small>
            </div>
            <div class="total-box">
                Total Pendapatan
                <span>Rp <?= number_format($display_total,0,',','.') ?></span>
            </div>
        </div>
 
        <div class="table-responsive">
            <table>
                <thead>
                <?php if ($view == 'bulan' || $view == 'tahun') { ?>
                    <tr>
                        <th>Periode</th>
                        <th>Jumlah TRX</th>
                        <th style="text-align:right;">Pendapatan</th>
                        <th style="text-align:center;">Laba Kotor</th>
                        <th style="text-align:center;">Laba Bersih</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                <?php } else { ?>
                    <tr>
                        <th>ID TRX</th>
                        <th>Waktu</th>
                        <th style="text-align:right;">Pendapatan</th>
                        <th style="text-align:center;">Laba Kotor</th>
                        <th style="text-align:center;">Laba Bersih</th>
                    </tr>
                <?php } ?>
                </thead>
     
                <tbody>
                <?php
                $res = mysqli_query($koneksi, $sql_tabel);
     
                if(mysqli_num_rows($res) > 0) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $pendapatan = ($view == 'bulan' || $view == 'tahun') ? $row['total'] : $row['total_pendapatan'];
                        $hpp = $row['total_hpp'] ?? 0;
                        
                        $laba_kotor_baris = $pendapatan - $hpp;
                        $laba_bersih_baris = $laba_kotor_baris - 0; // Baris detail TRX tidak dipotong rusak agar riwayat cocok
        
                        if ($view == 'bulan') {
                            $nm_bulan = date('F', mktime(0,0,0,$row['bln'],10));
                            echo "
                            <tr>
                                <td><strong>$nm_bulan</strong></td>
                                <td>{$row['jml']} TRX</td>
                                <td style='text-align:right'><strong>Rp ".number_format($row['total'],0,',','.')."</strong></td>
                                <td style='text-align:center'><span class='badge-laba-kotor'>Rp ".number_format($laba_kotor_baris,0,',','.')."</span></td>
                                <td style='text-align:center'><span class='badge-laba-bersih'>Rp ".number_format($laba_bersih_baris,0,',','.')."</span></td>
                                <td style='text-align:center'><a href='?view=hari' class='badge-link'>Detail</a></td>
                            </tr>";
                        }
                        elseif ($view == 'tahun') {
                            echo "
                            <tr>
                                <td><strong>{$row['thn']}</strong></td>
                                <td>{$row['jml']} TRX</td>
                                <td style='text-align:right'><strong>Rp ".number_format($row['total'],0,',','.')."</strong></td>
                                <td style='text-align:center'><span class='badge-laba-kotor'>Rp ".number_format($laba_kotor_baris,0,',','.')."</span></td>
                                <td style='text-align:center'><span class='badge-laba-bersih'>Rp ".number_format($laba_bersih_baris,0,',','.')."</span></td>
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
                                <td style='text-align:right'><strong>Rp ".number_format($row['total_pendapatan'],0,',','.')."</strong></td>
                                <td style='text-align:center'><span class='badge-laba-kotor'>Rp ".number_format($laba_kotor_baris,0,',','.')."</span></td>
                                <td style='text-align:center'><span class='badge-laba-bersih'>Rp ".number_format($laba_bersih_baris,0,',','.')."</span></td>
                            </tr>";
                        }
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; padding: 30px; color:var(--text-muted);'>Belum ada transaksi pada periode ini.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-tabel">
        <div class="table-header">
            <h3 style="color: var(--danger-text);"><i class="fa-solid fa-triangle-exclamation"></i> Riwayat Kerugian (Barang Rusak)</h3>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Produk</th>
                        <th style="text-align:center;">Jumlah Rusak</th>
                        <th style="text-align:right;">Nilai Kerugian</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $q_tabel_rugi = mysqli_query($koneksi, "
                        SELECT r.*, p.nama_produk 
                        FROM riwayat_kerugian r 
                        JOIN produk p ON r.id_produk = p.id_produk 
                        ORDER BY r.tanggal DESC LIMIT 50
                    ");
                    
                    if ($q_tabel_rugi && mysqli_num_rows($q_tabel_rugi) > 0) {
                        while($row = mysqli_fetch_assoc($q_tabel_rugi)):
                    ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                        <td><strong><?= $row['nama_produk'] ?></strong></td>
                        <td style="text-align:center;"><span class="badge-laba-kotor"><?= $row['jumlah_rusak'] ?> pcs</span></td>
                        <td style="text-align:right; font-weight:800; color:var(--danger-text);">- Rp <?= number_format($row['nilai_kerugian'], 0, ',', '.') ?></td>
                        <td style="color:var(--text-muted); font-size:12px;"><?= $row['keterangan'] ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding: 30px; color:var(--text-muted);'>Tidak ada data riwayat barang rusak/kerugian.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</main> <div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3 class="modal-title">Rincian Produk</h3>
        <div id="modal-body">Memuat data...</div>
    </div>
</div>

<script>
// Fungsi Download Kustom (PDF/EXCEL)
function generateDownload(type) {
    const start = document.getElementById('tgl_mulai').value;
    const end = document.getElementById('tgl_selesai').value;
    if (!start || !end) { alert("Silakan pilih tanggal terlebih dahulu"); return; }
    let url = type === 'pdf' ? `laporan_pdf.php?view=custom&start_date=${start}&end_date=${end}` : `laporan_excel.php?view=custom&start_date=${start}&end_date=${end}`;
    if (type === 'pdf') window.open(url, '_blank'); else window.location.href = url;
}

// Fungsi untuk mengambil detail transaksi via AJAX
function showDetail(id) {
    const modal = document.getElementById("myModal");
    const modalBody = document.getElementById("modal-body");
    
    modal.style.display = "block";
    modalBody.innerHTML = "Memuat data..."; // Pesan loading

    fetch('detailkasir.php?id=' + id)
        .then(response => response.text())
        .then(data => { modalBody.innerHTML = data; })
        .catch(error => { modalBody.innerHTML = "Gagal memuat data: " + error; });
}

// Fungsi untuk menutup modal
document.querySelector(".close").onclick = function() {
    document.getElementById("myModal").style.display = "none";
}

window.onclick = function(event) {
    const modal = document.getElementById("myModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>
<script src="laporan.js"></script>
</body>
</html>