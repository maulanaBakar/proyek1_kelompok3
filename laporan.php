<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if($_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login");
    exit();
}

$hari_ini = date('Y-m-d');
$bulan_ini = date('m');
$tahun_ini = date('Y');
$view = $_GET['view'] ?? 'hari';

// Query Statistik
$q_hari = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE DATE(tanggal_transaksi) = '$hari_ini'");
$total_hari = mysqli_fetch_assoc($q_hari)['total'] ?? 0;

$q_bulan = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE MONTH(tanggal_transaksi) = '$bulan_ini' AND YEAR(tanggal_transaksi) = '$tahun_ini'");
$total_bulan = mysqli_fetch_assoc($q_bulan)['total'] ?? 0;

$q_tahun = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE YEAR(tanggal_transaksi) = '$tahun_ini'");
$total_tahun = mysqli_fetch_assoc($q_tahun)['total'] ?? 0;

if($view == 'bulan') {
    $title = "Rekap Penjualan Bulanan - $tahun_ini";
    $display_total = $total_bulan;
    $sql_tabel = "SELECT MONTH(tanggal_transaksi) as bln, SUM(total_pendapatan) as total, COUNT(*) as jml FROM transaksi WHERE YEAR(tanggal_transaksi) = '$tahun_ini' GROUP BY MONTH(tanggal_transaksi) ORDER BY bln DESC";
} elseif($view == 'tahun') {
    $title = "Rekap Penjualan Tahunan";
    $display_total = $total_tahun;
    $sql_tabel = "SELECT YEAR(tanggal_transaksi) as thn, SUM(total_pendapatan) as total, COUNT(*) as jml FROM transaksi GROUP BY YEAR(tanggal_transaksi) ORDER BY thn DESC";
} else {
    $title = "Riwayat Transaksi Terakhir";
    
    // Ambil total semua pendapatan agar tidak 0 meskipun hari ini belum ada transaksi baru.
    $q_total_all = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi");
    $res_total = mysqli_fetch_assoc($q_total_all);
    $display_total = $res_total['total'] ?? 0;
    
    $sql_tabel = "SELECT * FROM transaksi ORDER BY tanggal_transaksi DESC LIMIT 50";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2 Paksi | Laporan</title>

    <link rel="stylesheet" href="laporan.css"> <!-- INI -->
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <div class="mobile-toggle">
        <div class="brand">2 PAKSI</div>
        <button id="menu-btn"><i class="fa-solid fa-bars"></i></button>
    </div>

    <div class="sidebar" id="sidebar">
        <div>
            <div class="logo">2 PAKSI</div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link"><i class="fa-solid fa-gauge"></i> Beranda</a>
                <a href="kasir.php" class="nav-link"><i class="fa-solid fa-cash-register"></i> Kasir</a>
                <a href="stok.php" class="nav-link"><i class="fa-solid fa-box"></i> Stok barang</a>
                <a href="laporan.php" class="nav-link active"><i class="fa-solid fa-file-invoice-dollar"></i> Laporan keuangan</a>
            </div>
        </div>
        <div class="logout-section">
            <a href="logout.php" class="nav-link logout-link"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
        </div>
    </div>

    <main class="content">
        <div class="header">
            <h1>Laporan Keuangan</h1>
        </div>

        <div class="stats-container">
            <a href="?view=hari" class="card-stat <?= $view=='hari'?'active':'' ?>">
                <p>Perhari (Hari Ini)</p>
                <h3>Rp <?= number_format($total_hari, 0, ',', '.') ?></h3>
            </a>
            <a href="?view=bulan" class="card-stat <?= $view=='bulan'?'active':'' ?>">
                <p>Perbulan (Bulan Ini)</p>
                <h3>Rp <?= number_format($total_bulan, 0, ',', '.') ?></h3>
            </a>
            <a href="?view=tahun" class="card-stat <?= $view=='tahun'?'active':'' ?>">
                <p>Pertahun (<?= $tahun_ini ?>)</p>
                <h3>Rp <?= number_format($total_tahun, 0, ',', '.') ?></h3>
            </a>
        </div>

        <div class="report-card">
            <div class="table-header">
                <h3><?= $title ?></h3>
                <div class="total-box">Total Pendapatan <span>Rp <?= number_format($display_total, 0, ',', '.') ?></span></div>
            </div>

            <table>
                <thead>
                    <?php if($view == 'bulan' || $view == 'tahun'): ?>
                        <tr><th>PERIODE</th><th>JUMLAH TRX</th><th style="text-align: right;">PENDAPATAN</th><th style="text-align: center;">AKSI</th></tr>
                    <?php else: ?>
                        <tr><th>ID_TRANSAKSI</th><th>WAKTU</th><th>STATUS</th><th style="text-align: right;">JUMLAH</th></tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php
                    $res = mysqli_query($koneksi, $sql_tabel);
                    while($row = mysqli_fetch_assoc($res)) {
                        if($view == 'bulan') {
                            $nm_bulan = date('F', mktime(0,0,0,$row['bln'], 10));
                            echo "<tr>
                                    <td><strong style='font-weight:700;'>$nm_bulan</strong></td>
                                    <td><span style='color:var(--text-muted);'>{$row['jml']} Trx</span></td>
                                    <td style='text-align:right;' class='price'>Rp ".number_format($row['total'], 0, ',', '.')."</td>
                                    <td style='text-align:center;'><a href='?view=hari' class='badge-link'>Detail</a></td>
                                  </tr>";
                        } elseif($view == 'tahun') {
                            echo "<tr>
                                    <td><strong style='font-weight:700;'>{$row['thn']}</strong></td>
                                    <td><span style='color:var(--text-muted);'>{$row['jml']} Trx</span></td>
                                    <td style='text-align:right;' class='price'>Rp ".number_format($row['total'], 0, ',', '.')."</td>
                                    <td style='text-align:center;'><a href='?view=bulan' class='badge-link'>Lihat Bulanan</a></td>
                                  </tr>";
                        } else {
                            echo "<tr>
                                    <td><button onclick='showDetail({$row['id_transaksi']})' class='btn-trx'>#TRX_{$row['id_transaksi']}</button></td>
                                    <td style='color:var(--text-muted);'>".date('d M Y • H:i', strtotime($row['tanggal_transaksi']))."</td>
                                    <td><span style='color:#27ae60; font-size:12px; font-weight:700;'><i class='fa-solid fa-circle-check'></i> Berhasil</span></td>
                                    <td style='text-align:right;' class='price'>Rp ".number_format($row['total_pendapatan'], 0, ',', '.')."</td>
                                  </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 class="modal-title">Rincian Produk</h3>
            <div id="modal-body">
                Memuat data...
            </div>
        </div>
    </div>

    <script>
        // SCRIPT MENU TOGGLE UNTUK HP
        const menuBtn = document.getElementById('menu-btn');
        const sidebar = document.getElementById('sidebar');
        
        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !menuBtn.contains(e.target) && window.innerWidth <= 992) {
                sidebar.classList.remove('active');
            }
        });

        // MODAL HANDLER
        function showDetail(id) {
            document.getElementById('myModal').style.display = "block";
            document.getElementById('modal-body').innerHTML = "Memuat data...";
            
            fetch('get_detail.php?id=' + id)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modal-body').innerHTML = data;
                });
        }

        function closeModal() {
            document.getElementById('myModal').style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('myModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>