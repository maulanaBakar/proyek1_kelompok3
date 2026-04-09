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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --sidebar-bg: #4A3E3D; /* Cokelat Tua Hangat */
            --sidebar-hover: rgba(255, 255, 255, 0.08);
            --accent-color: #D6C4B0; /* Krem Susu */
            --accent-hover: #C4B19C; 
            --bg-body: #FDFBF7; /* Putih rona Krem */
            --text-dark: #3E3636;
            --text-muted: #8C7A78;
            --white: #ffffff;
            --sidebar-width: 260px;
            --shadow-sm: 0 1px 3px rgba(74, 62, 61, 0.05);
            --shadow-md: 0 4px 12px rgba(74, 62, 61, 0.08);
        }
        
        * { margin:0; padding:0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-body); display: flex; min-height: 100vh; color: var(--text-dark); }

        /* --- MOBILE HEADER TOGGLE --- */
        .mobile-toggle {
            display: none;
            background-color: var(--white);
            padding: 15px 20px;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #F2EBE1;
        }
        .mobile-toggle .brand { font-weight: 800; color: var(--sidebar-bg); letter-spacing: 1px; }
        .mobile-toggle button { background: none; border: none; font-size: 20px; color: var(--sidebar-bg); cursor: pointer; }

        /* --- SIDEBAR --- */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            padding: 30px 20px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            z-index: 1001;
            transition: all 0.3s ease;
        }
        .logo { font-size: 22px; font-weight: 800; text-align: center; letter-spacing: 1.5px; color: var(--accent-color); padding-bottom: 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .nav-menu { list-style: none; margin-top: 25px; flex-grow: 1; }
        .nav-item { margin-bottom: 8px; }
        .nav-link { display: flex; align-items: center; padding: 14px 18px; color: #D2C4C3; text-decoration: none; border-radius: 12px; font-size: 14px; font-weight: 600; transition: all 0.3s ease; }
        .nav-link i { margin-right: 14px; font-size: 18px; width: 20px; text-align: center; }
        .nav-link:hover { background: var(--sidebar-hover); color: var(--accent-color); }
        .nav-link.active { background: var(--accent-color); color: var(--sidebar-bg); box-shadow: 0 4px 12px rgba(214, 196, 176, 0.2); }
        .logout-section { border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 20px; }
        .logout-link { color: #FCA5A5; }
        .logout-link:hover { background: rgba(239, 68, 68, 0.1); color: #EF4444; }

        /* --- MAIN CONTENT --- */
        .content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); padding: 40px 50px; transition: all 0.3s ease; }
        .header h1 { font-size: 26px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px; margin-bottom: 30px; }

        /* --- STATS CARDS --- */
        .stats-container { display: flex; gap: 20px; margin-bottom: 35px; }
        .card-stat { 
            flex: 1; 
            padding: 22px; 
            border-radius: 16px; 
            background: var(--white); 
            text-decoration: none; 
            color: var(--text-dark); 
            border: 1px solid #F2EBE1; 
            transition: all 0.3s ease; 
            box-shadow: var(--shadow-sm);
        }
        .card-stat:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .card-stat.active { background: var(--sidebar-bg); color: var(--white); border: none; }
        .card-stat p { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px; }
        .card-stat.active p { color: rgba(255, 255, 255, 0.6); }
        .card-stat h3 { font-size: 20px; font-weight: 800; }
        .card-stat.active h3 { color: var(--accent-color); }

        /* --- REPORT CARD & TABLE --- */
        .report-card { background: var(--white); border-radius: 20px; padding: 25px; box-shadow: var(--shadow-sm); border: 1px solid #F2EBE1; }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 10px; }
        .table-header h3 { font-size: 16px; font-weight: 800; }
        
        .total-box { background: #FDFBF7; padding: 10px 18px; border-radius: 12px; color: var(--text-muted); font-weight: 700; font-size: 12px; border: 1px solid #F2EBE1; }
        .total-box span { color: var(--text-dark); margin-left: 8px; background: var(--accent-color); padding: 4px 10px; border-radius: 8px; font-weight: 800; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 16px; color: var(--text-muted); font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #FDFBF7; letter-spacing: 0.5px; font-weight: 700; }
        td { padding: 16px; font-size: 13px; border-bottom: 1px solid #FDFBF7; color: var(--text-dark); }
        tr:last-child td { border-bottom: none; }
        
        .btn-trx { background: #FDFBF7; color: var(--text-dark); padding: 6px 14px; border-radius: 8px; text-decoration: none; font-size: 11px; font-weight: 700; cursor: pointer; border: 1px solid #F2EBE1; transition: 0.2s; }
        .btn-trx:hover { background: var(--sidebar-bg); color: var(--accent-color); border-color: var(--sidebar-bg); }
        .price { color: var(--text-dark); font-weight: 700; }
        
        .badge-link { display: inline-block; padding: 5px 12px; background: #FDFBF7; color: var(--text-dark); text-decoration: none; border-radius: 8px; font-size: 11px; font-weight: 700; border: 1px solid #F2EBE1; transition: 0.2s; }
        .badge-link:hover { background: var(--accent-color); color: var(--sidebar-bg); border-color: var(--accent-color); }

        /* --- MODAL CSS --- */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(74, 62, 61, 0.5); backdrop-filter: blur(4px); }
        .modal-content { background: var(--white); margin: 10% auto; padding: 25px; border-radius: 20px; width: 400px; position: relative; animation: slideUp 0.4s ease; box-shadow: 0 20px 40px rgba(0,0,0,0.15); border: 1px solid rgba(255, 255, 255, 0.8); }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .close { position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer; color: var(--text-muted); }
        .close:hover { color: var(--text-dark); }
        .modal-title { font-weight: 800; font-size: 16px; margin-bottom: 20px; color: var(--text-dark); border-bottom: 1px solid #FDFBF7; padding-bottom: 10px; }
        .item-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #F2EBE1; font-size: 13px; color: var(--text-dark); }
        .item-row:last-child { border-bottom: none; }

        /* --- RESPONSIVE MOBILE --- */
        @media (max-width: 992px) {
            body { flex-direction: column; }
            .mobile-toggle { display: flex; }
            .sidebar { width: 240px; transform: translateX(-240px); }
            .sidebar.active { transform: translateX(0); }
            .content { margin-left: 0; width: 100%; padding: 90px 20px 30px 20px; }
            .stats-container { flex-direction: column; }
            .modal-content { width: 90%; }
        }
    </style>
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