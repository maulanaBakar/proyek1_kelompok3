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
 
$hari_ini   = date('Y-m-d');
$bulan_ini = date('m');
$tahun_ini = date('Y');

// ====================================================
// FITUR VOID: BATALKAN TRANSAKSI 
// ====================================================
if (isset($_GET['void'])) {
    $id_trx = mysqli_real_escape_string($koneksi, $_GET['void']);

    // 1. Memulai Database Transaction (Locking) untuk mencegah data berantakan jika server error di tengah proses
    mysqli_begin_transaction($koneksi);

    try {
        // 2. Kembalikan stok produk ke etalase
        $q_detail = mysqli_query($koneksi, "SELECT id_produk, jumlah_produk FROM detail_transaksi WHERE id_transaksi = '$id_trx'");
        while ($d = mysqli_fetch_assoc($q_detail)) {
            $id_p = $d['id_produk'];
            $qty = $d['jumlah_produk'];
            mysqli_query($koneksi, "UPDATE produk SET stok = stok + $qty WHERE id_produk = '$id_p'");
        }

        // 3. Hapus data produk dari detail_transaksi
        mysqli_query($koneksi, "DELETE FROM detail_transaksi WHERE id_transaksi = '$id_trx'");

        // 4. PERBAIKAN BUG: Hapus data keuangan yang terlanjur masuk di tabel buku_kas
        // Menghapus data kas berdasarkan ID transaksi (Asumsi keterangan kas mengandung ID transaksi, misal "TRX_123" atau "Transaksi 123")
        mysqli_query($koneksi, "DELETE FROM buku_kas WHERE keterangan LIKE '%$id_trx%'");

        // 5. Hapus data dari tabel transaksi utama
        mysqli_query($koneksi, "DELETE FROM transaksi WHERE id_transaksi = '$id_trx'");

        // 6. Jika SEMUA proses di atas sukses tanpa error, simpan perubahan secara permanen (Commit)
        mysqli_commit($koneksi);

        echo "<script>alert('Transaksi #TRX_$id_trx berhasil dibatalkan. Stok dan Saldo Buku Kas telah dikoreksi!'); window.location='laporan.php';</script>";
        exit;

    } catch (Exception $e) {
        // 7. Jika ada salah satu query yang gagal, kembalikan semua data seperti semula (Rollback)
        mysqli_rollback($koneksi);
        
        echo "<script>alert('Gagal membatalkan transaksi: " . $e->getMessage() . "'); window.location='laporan.php';</script>";
        exit;
    }
}

// --- LOGIKA FILTER PERIODE ---
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

/* Statistik Ringkasan Atas */
$q_hari = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE DATE(tanggal_transaksi)='$hari_ini'");
$total_hari = mysqli_fetch_assoc($q_hari)['total'] ?? 0;

$q_bulan = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE MONTH(tanggal_transaksi)='$bulan_ini' AND YEAR(tanggal_transaksi)='$tahun_ini'");
$total_bulan = mysqli_fetch_assoc($q_bulan)['total'] ?? 0;

$q_tahun = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE YEAR(tanggal_transaksi)='$tahun_ini'");
$total_tahun = mysqli_fetch_assoc($q_tahun)['total'] ?? 0;

/* Penentuan SQL Tabel & Periode */
if ($view == 'bulan') {
    $title = "Rekap Penjualan Bulanan ($tahun_ini)";
    $display_total = $total_bulan;
    $sql_tabel = "SELECT MONTH(t.tanggal_transaksi) as bln, COUNT(t.id_transaksi) as jml, SUM(t.total_pendapatan) as total, SUM(hpp_trx.total_hpp) as total_hpp
                  FROM transaksi t LEFT JOIN $sql_hpp hpp_trx ON t.id_transaksi = hpp_trx.id_transaksi
                  WHERE YEAR(t.tanggal_transaksi)='$tahun_ini' GROUP BY MONTH(t.tanggal_transaksi) ORDER BY bln DESC";
    $w_trx = "MONTH(t.tanggal_transaksi) = '$bulan_ini' AND YEAR(t.tanggal_transaksi) = '$tahun_ini'";
    $w_kas = "MONTH(tanggal) = '$bulan_ini' AND YEAR(tanggal) = '$tahun_ini'";
    $w_tgl = "MONTH(tanggal) = '$bulan_ini' AND YEAR(tanggal) = '$tahun_ini'";
} elseif ($view == 'tahun') {
    $title = "Rekap Penjualan Tahunan";
    $display_total = $total_tahun;
    $sql_tabel = "SELECT YEAR(t.tanggal_transaksi) as thn, COUNT(t.id_transaksi) as jml, SUM(t.total_pendapatan) as total, SUM(hpp_trx.total_hpp) as total_hpp
                  FROM transaksi t LEFT JOIN $sql_hpp hpp_trx ON t.id_transaksi = hpp_trx.id_transaksi
                  GROUP BY YEAR(t.tanggal_transaksi) ORDER BY thn DESC";
    $w_trx = "YEAR(t.tanggal_transaksi) = '$tahun_ini'";
    $w_kas = "YEAR(tanggal) = '$tahun_ini'";
    $w_tgl = "YEAR(tanggal) = '$tahun_ini'";
} elseif ($view == 'custom') {
    $title = "Laporan: " . date('d/m/y', strtotime($tgl_mulai)) . " - " . date('d/m/y', strtotime($tgl_selesai));
    $q_custom = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE DATE(tanggal_transaksi) BETWEEN '$tgl_mulai' AND '$tgl_selesai'");
    $display_total = mysqli_fetch_assoc($q_custom)['total'] ?? 0;
    $sql_tabel = "SELECT t.*, hpp_trx.total_hpp FROM transaksi t LEFT JOIN $sql_hpp hpp_trx ON t.id_transaksi = hpp_trx.id_transaksi 
                  WHERE DATE(t.tanggal_transaksi) BETWEEN '$tgl_mulai' AND '$tgl_selesai' ORDER BY t.tanggal_transaksi DESC";
    $w_trx = "DATE(t.tanggal_transaksi) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
    $w_kas = "DATE(tanggal) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
    $w_tgl = "tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
} else { 
    $title = "Riwayat Transaksi Hari Ini";
    $display_total = $total_hari;
    $sql_tabel = "SELECT t.*, hpp_trx.total_hpp FROM transaksi t LEFT JOIN $sql_hpp hpp_trx ON t.id_transaksi = hpp_trx.id_transaksi 
                  WHERE DATE(t.tanggal_transaksi) = '$hari_ini' ORDER BY t.tanggal_transaksi DESC";
    $w_trx = "DATE(t.tanggal_transaksi) = '$hari_ini'";
    $w_kas = "DATE(tanggal) = '$hari_ini'";
    $w_tgl = "tanggal = '$hari_ini'";
}


$q_v_penjualan = mysqli_query($koneksi, "
    SELECT SUM(d.subtotal - (d.jumlah_produk * IF(p.jenis_produk = 'Luar', p.modal, p.hpp))) as laba_kotor
    FROM transaksi t JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi JOIN produk p ON d.id_produk = p.id_produk
    WHERE $w_trx
");
$v_laba_kotor = mysqli_fetch_assoc($q_v_penjualan)['laba_kotor'] ?? 0;

// 2. Filter Pengeluaran dari Buku Kas
$q_kas = mysqli_query($koneksi, "SELECT keterangan, nominal FROM buku_kas WHERE jenis = 'Pengeluaran' AND $w_kas");
$v_pengeluaran = 0; // Operasional
$v_prive_uang = 0;
$v_belanja_stok = 0;

while($rk = mysqli_fetch_assoc($q_kas)){
    $ket = strtolower($rk['keterangan']);
    if(strpos($ket, '[prive]') !== false || strpos($ket, 'prive') !== false || strpos($ket, 'ambil') !== false){
        $v_prive_uang += $rk['nominal'];
    } elseif(strpos($ket, '[belanja stok]') !== false){
        $v_belanja_stok += $rk['nominal']; // Tidak mengurangi laba bersih (karena jadi aset barang)
    } else {
        // Anggap sebagai Operasional/Lainnya
        $v_pengeluaran += $rk['nominal'];
    }
}

// 3. Barang Rusak (Hitung Dinamis)
$q_v_rusak = mysqli_query($koneksi, "
    SELECT SUM(r.jumlah_rusak * IF(p.jenis_produk = 'Luar', p.modal, p.hpp)) as total 
    FROM riwayat_kerugian r 
    JOIN produk p ON r.id_produk = p.id_produk 
    WHERE $w_tgl
");
$v_rusak = mysqli_fetch_assoc($q_v_rusak)['total'] ?? 0;

// 4. Prive Barang (Hitung Dinamis)
$q_v_prive_barang = mysqli_query($koneksi, "
    SELECT SUM(pr.jumlah * IF(p.jenis_produk = 'Luar', p.modal, p.hpp)) as total 
    FROM prive_barang pr 
    JOIN produk p ON pr.id_produk = p.id_produk 
    WHERE $w_tgl
");
$v_prive_barang = mysqli_fetch_assoc($q_v_prive_barang)['total'] ?? 0;

// 5. Kasbon / Piutang (Menghitung Total Transaksi - Uang yang diterima)
$q_kasbon = mysqli_query($koneksi, "
    SELECT SUM(t.total_pendapatan - t.uang_diterima) as piutang 
    FROM transaksi t
    WHERE t.uang_diterima < t.total_pendapatan AND $w_trx
");
$v_piutang = mysqli_fetch_assoc($q_kasbon)['piutang'] ?? 0;
if($v_piutang < 0) $v_piutang = 0; // Memastikan tidak error

// Final Kalkulasi
$v_total_prive = $v_prive_uang + $v_prive_barang; 


$v_laba_bersih = $v_laba_kotor - $v_pengeluaran - $v_rusak - $v_total_prive; 
$v_sisa_kekayaan = $v_laba_bersih - $v_piutang;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2 Paksi | Laporan</title>
    <link rel="stylesheet" href="css/laporan.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        .grid-laporan { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; margin-top: 10px; }
        .card-lap { background: white; padding: 15px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); text-align: center; border-bottom: 4px solid #eee; }
        .card-lap h4 { margin: 0 0 10px 0; color: #666; font-size: 0.85em; text-transform: uppercase; font-weight: 800;}
        .card-lap h2 { margin: 0; font-size: 1.5em; color: #333; }
        
        .c-laba { border-color: #2ecc71; } .c-laba h2 { color: #2ecc71; }
        .c-keluar { border-color: #e67e22; } .c-keluar h2 { color: #e67e22; }
        .c-rusak { border-color: #e74c3c; } .c-rusak h2 { color: #e74c3c; }
        .c-prive { border-color: #9b59b6; } .c-prive h2 { color: #9b59b6; }
        .c-kasbon { border-color: #f39c12; } .c-kasbon h2 { color: #f39c12; } /* Warna Piutang Kasbon */
        
        .card-bersih { background: var(--primary, #4a3e3d); color: white; padding: 25px; border-radius: 12px; text-align: center; margin-bottom: 25px; box-shadow: 0 6px 15px rgba(0,0,0,0.1);}
        .card-bersih h1 { margin: 10px 0; font-size: 2.2em; color: #2ecc71; text-shadow: 0 2px 4px rgba(0,0,0,0.2);}
    </style>
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
            <a href="pengaturan.php" class="link-menu"><i class="fa-solid fa-gear"></i> Pengaturan</a>
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

    <h3 style="color: #4a3e3d; margin-top: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <i class="fa-solid fa-chart-pie"></i> Analisis Laba Rugi & Aset (Sesuai Periode)
    </h3>
    
    <div class="grid-laporan">
        <div class="card-lap c-laba">
            <h4><i class="fa-solid fa-arrow-trend-up"></i> Laba Kotor</h4>
            <h2>Rp <?= number_format($v_laba_kotor, 0, ',', '.') ?></h2>
        </div>
        <div class="card-lap c-keluar">
            <h4><i class="fa-solid fa-money-bill-transfer"></i> Operasional</h4>
            <h2>Rp <?= number_format($v_pengeluaran, 0, ',', '.') ?></h2>
        </div>
        <div class="card-lap c-rusak">
            <h4><i class="fa-solid fa-triangle-exclamation"></i> Kerugian Rusak</h4>
            <h2>Rp <?= number_format($v_rusak, 0, ',', '.') ?></h2>
        </div>
        <div class="card-lap c-prive">
            <h4><i class="fa-solid fa-hand-holding-heart"></i> Total Prive</h4>
            <h2>Rp <?= number_format($v_total_prive, 0, ',', '.') ?></h2>
        </div>
        <div class="card-lap c-kasbon">
            <h4><i class="fa-solid fa-file-invoice-dollar"></i> Piutang Kasbon</h4>
            <h2>Rp <?= number_format($v_piutang, 0, ',', '.') ?></h2>
        </div>
    </div>

    <div class="card-bersih">
        <p style="font-weight: bold; letter-spacing: 1px; text-transform: uppercase;">Laba Bersih Pembukuan (Net Profit)</p>
        <small style="color:#ddd;">Laba Kotor - Biaya Operasional - Kerugian Barang Rusak - Total Prive</small>
        <h1>Rp <?= number_format($v_laba_bersih, 0, ',', '.') ?></h1>
        <div style="margin-top:10px; background:rgba(255,255,255,0.15); display:inline-block; padding:8px 15px; border-radius:20px; font-size:0.95em;">
            Estimasi Uang Cash Fisik (Dipotong Prive & Orang Ngutang/Kasbon): <strong style="color: #f1c40f;">Rp <?= number_format($v_sisa_kekayaan, 0, ',', '.') ?></strong>
        </div>
    </div>

    <div class="report-card">
        <div class="table-header">
            <h3><i class="fa-solid fa-list"></i> <?= $title ?></h3>
            <div class="total-box">Total Omset: <span>Rp <?= number_format($display_total,0,',','.') ?></span></div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <?php if ($view == 'bulan' || $view == 'tahun'): ?>
                        <tr><th>Periode</th><th>Transaksi</th><th>Pendapatan</th><th>Laba Kotor</th><th>Aksi</th></tr>
                    <?php else: ?>
                        <tr>
                            <th>ID Transaksi</th>
                            <th>Waktu / Kasbon</th>
                            <th>Pendapatan</th>
                            <th>Laba Kotor</th>
                            <th style="text-align: center;">Aksi</th> 
                        </tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                <?php
                $res = mysqli_query($koneksi, $sql_tabel);
                if(mysqli_num_rows($res) > 0) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $pendapatan = ($view == 'bulan' || $view == 'tahun') ? $row['total'] : $row['total_pendapatan'];
                        $laba_kotor_row = $pendapatan - ($row['total_hpp'] ?? 0);

                        if ($view == 'bulan') {
                            $nm_bulan = date('F', mktime(0,0,0,$row['bln'],10));
                            $link = "?view=custom&start_date=$tahun_ini-".sprintf('%02d',$row['bln'])."-01&end_date=$tahun_ini-".sprintf('%02d',$row['bln'])."-31";
                            echo "<tr><td><strong>$nm_bulan</strong></td><td>{$row['jml']} TRX</td><td>Rp ".number_format($pendapatan,0,',','.')."</td><td>Rp ".number_format($laba_kotor_row,0,',','.')."</td><td><a href='$link' class='badge-link'>Detail</a></td></tr>";
                        } elseif ($view == 'tahun') {
                            $link = "?view=custom&start_date={$row['thn']}-01-01&end_date={$row['thn']}-12-31";
                            echo "<tr><td><strong>{$row['thn']}</strong></td><td>{$row['jml']} TRX</td><td>Rp ".number_format($pendapatan,0,',','.')."</td><td>Rp ".number_format($laba_kotor_row,0,',','.')."</td><td><a href='$link' class='badge-link'>Lihat</a></td></tr>";
                        } else {
                            // Cek jika kasbon
                           // Cek jika kasbon
$label_kasbon = ($row['uang_diterima'] < $pendapatan) ? "<br><span style='font-size:0.8em; color:#e74c3c; font-weight:bold;'>BELUM LUNAS: Rp ".number_format($pendapatan - $row['uang_diterima'],0,',','.')."</span>" : "";
                            echo "<tr>
                                <td><button onclick='showDetail({$row['id_transaksi']})' class='btn-trx'>#TRX_{$row['id_transaksi']}</button></td>
                                <td>".date('d M Y H:i', strtotime($row['tanggal_transaksi'])).$label_kasbon."</td>
                                <td>Rp ".number_format($pendapatan,0,',','.')."</td>
                                <td>Rp ".number_format($laba_kotor_row,0,',','.')."</td>
                                <td style='text-align: center;'>
                                    <a href='laporan.php?void={$row['id_transaksi']}' onclick=\"return confirm('AWAS! Yakin ingin membatalkan transaksi ini?')\" style='background: #e74c3c; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85em; font-weight: bold;'><i class='fa-solid fa-rotate-left'></i> Batal (Void)</a>
                                </td>
                            </tr>";
                        }
                    }
                } else { echo "<tr><td colspan='5' style='text-align:center; padding:20px;'>Tidak ada data.</td></tr>"; }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="report-card" style="margin-top: 30px;">
        <div class="table-header" style="border-bottom: 2px solid #e74c3c;">
            <h3 style="color: #e74c3c;"><i class="fa-solid fa-circle-exclamation"></i> Detail Barang Rusak</h3>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Produk</th>
                        <th style="text-align: center;">Jumlah</th>
                        <th>Nilai Kerugian</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $q_detail_rusak = mysqli_query($koneksi, "
                    SELECT r.*, p.nama_produk, p.jenis_produk, p.modal, p.hpp 
                    FROM riwayat_kerugian r JOIN produk p ON r.id_produk = p.id_produk 
                    WHERE $w_tgl ORDER BY r.tanggal DESC
                ");

                if(mysqli_num_rows($q_detail_rusak) > 0) {
                    while($row = mysqli_fetch_assoc($q_detail_rusak)) {
                        $is_koreksi = $row['jumlah_rusak'] < 0;
                        $tanda_koreksi = $is_koreksi ? "<span style='color:#2ecc71; font-weight:bold;'>Koreksi: " : "<span style='color:#e74c3c; font-weight:bold;'>";
                        
                        // Kalkulasi manual nilai kerugian
                        $harga_modal = ($row['jenis_produk'] == 'Luar') ? $row['modal'] : $row['hpp'];
                        $nilai_kerugian_asli = abs($row['jumlah_rusak']) * $harga_modal;

                        echo "<tr>
                            <td>".date('d/m/Y', strtotime($row['tanggal']))."</td>
                            <td><strong>".htmlspecialchars($row['nama_produk'])."</strong></td>
                            <td style='text-align:center;'>".$tanda_koreksi.$row['jumlah_rusak']." Pcs</span></td>
                            <td>Rp ".number_format($nilai_kerugian_asli, 0, ',', '.')."</td>
                            <td><small>".htmlspecialchars($row['keterangan'])."</small></td>
                        </tr>";
                    }
                } else { echo "<tr><td colspan='5' style='text-align:center; padding:20px; color:#888;'>Tidak ada riwayat kerusakan pada periode ini.</td></tr>"; }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="report-card" style="margin-top: 30px;">
        <div class="table-header" style="border-bottom: 2px solid #9b59b6;">
            <h3 style="color: #9b59b6;"><i class="fa-solid fa-hand-holding-heart"></i> Detail Keperluan Lain / Prive Barang</h3>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Produk</th>
                        <th style="text-align: center;">Jumlah Ambil</th>
                        <th>Nilai Modal Keluar (Prive)</th>
                        <th>Untuk Siapa / Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $q_detail_prive = mysqli_query($koneksi, "
                    SELECT pr.*, p.nama_produk, p.jenis_produk, p.modal, p.hpp 
                    FROM prive_barang pr JOIN produk p ON pr.id_produk = p.id_produk 
                    WHERE $w_tgl ORDER BY pr.tanggal DESC
                ");

                if(mysqli_num_rows($q_detail_prive) > 0) {
                    while($rp = mysqli_fetch_assoc($q_detail_prive)) {
                        
                        // Kalkulasi manual nilai prive
                        $harga_modal_prive = ($rp['jenis_produk'] == 'Luar') ? $rp['modal'] : $rp['hpp'];
                        $nilai_prive_asli = $rp['jumlah'] * $harga_modal_prive;

                        echo "<tr>
                            <td>".date('d/m/Y', strtotime($rp['tanggal']))."</td>
                            <td><strong>".htmlspecialchars($rp['nama_produk'])."</strong></td>
                            <td style='text-align:center;'><span style='color:#9b59b6; font-weight:bold;'>".$rp['jumlah']." Pcs</span></td>
                            <td>Rp ".number_format($nilai_prive_asli, 0, ',', '.')."</td>
                            <td><small>".htmlspecialchars($rp['keterangan'])."</small></td>
                        </tr>";
                    }
                } else { echo "<tr><td colspan='5' style='text-align:center; padding:20px; color:#888;'>Tidak ada pengambilan barang (Prive) pada periode ini.</td></tr>"; }
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