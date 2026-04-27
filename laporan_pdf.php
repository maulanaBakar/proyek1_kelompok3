<?php
/**
 * laporan_pdf.php  — TANPA COMPOSER / TANPA LIBRARY EKSTERNAL
 * 
 * Cara kerja:
 * - Generate halaman HTML yang dioptimalkan untuk print
 * - Klik tombol "Cetak / Simpan PDF" → browser buka dialog Print
 * - Pilih "Save as PDF" / "Microsoft Print to PDF" → file .pdf tersimpan
 */

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

/* ===== Statistik ===== */
$q_hari  = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE DATE(tanggal_transaksi)='$hari_ini'");
$total_hari  = mysqli_fetch_assoc($q_hari)['total']  ?? 0;

$q_bulan = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE MONTH(tanggal_transaksi)='$bulan_ini' AND YEAR(tanggal_transaksi)='$tahun_ini'");
$total_bulan = mysqli_fetch_assoc($q_bulan)['total'] ?? 0;

$q_tahun = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE YEAR(tanggal_transaksi)='$tahun_ini'");
$total_tahun = mysqli_fetch_assoc($q_tahun)['total'] ?? 0;

if ($view == 'custom') {
    $start = $_GET['start_date'] ?? $hari_ini;
    $end   = $_GET['end_date'] ?? $hari_ini;
    
    $title         = "Laporan Periode: " . date('d/m/Y', strtotime($start)) . " - " . date('d/m/Y', strtotime($end));
    $sql           = "SELECT * FROM transaksi WHERE DATE(tanggal_transaksi) BETWEEN '$start' AND '$end' ORDER BY tanggal_transaksi ASC";
    
    // Hitung total khusus untuk range ini
    $q_total_c     = mysqli_query($koneksi, "SELECT SUM(total_pendapatan) as total FROM transaksi WHERE DATE(tanggal_transaksi) BETWEEN '$start' AND '$end'");
    $display_total = mysqli_fetch_assoc($q_total_c)['total'] ?? 0;

} elseif ($view == 'bulan') {
    $title         = "Rekap Penjualan Bulanan &mdash; Tahun $tahun_ini";
    $display_total = $total_bulan;
    $sql           = "SELECT MONTH(tanggal_transaksi) as bln, SUM(total_pendapatan) as total, COUNT(*) as jml
                      FROM transaksi WHERE YEAR(tanggal_transaksi)='$tahun_ini'
                      GROUP BY MONTH(tanggal_transaksi) ORDER BY bln ASC";

} elseif ($view == 'tahun') {
    $title         = "Rekap Penjualan Tahunan";
    $display_total = $total_tahun;
    $sql           = "SELECT YEAR(tanggal_transaksi) as thn, SUM(total_pendapatan) as total, COUNT(*) as jml
                      FROM transaksi GROUP BY YEAR(tanggal_transaksi) ORDER BY thn DESC";

} else {
    // Default: Hari Ini
    $title         = "Riwayat Transaksi Harian &mdash; " . date('d F Y');
    $display_total = $total_hari;
    $sql           = "SELECT * FROM transaksi WHERE DATE(tanggal_transaksi)='$hari_ini' ORDER BY tanggal_transaksi DESC LIMIT 500";
}

$res = mysqli_query($koneksi, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan 2 Paksi</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: Arial, sans-serif;
    font-size: 12px;
    color: #1a1a2e;
    background: #fff;
    padding: 24px 32px;
  }

  /* Tombol tidak ikut cetak */
  .print-bar {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-bottom: 20px;
  }
  .btn-print, .btn-back {
    padding: 9px 20px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .btn-print { background: #1a73e8; color: #fff; }
  .btn-back  { background: #f0f0f0; color: #333; }

  /* Header */
  .doc-header {
    text-align: center;
    border-bottom: 3px solid #1a73e8;
    padding-bottom: 14px;
    margin-bottom: 18px;
  }
  .doc-header .brand    { font-size: 22px; font-weight: 800; color: #1a73e8; letter-spacing: 2px; }
  .doc-header .subtitle { font-size: 14px; font-weight: 700; color: #333; margin-top: 4px; }
  .doc-header .meta     { font-size: 10px; color: #888; margin-top: 3px; }

  /* Statistik */
  .stats-row { display: flex; gap: 12px; margin-bottom: 18px; }
  .stat-box  { flex: 1; border: 1.5px solid #dde3f0; border-radius: 8px; padding: 10px 14px; }
  .stat-box.active { border-color: #1a73e8; background: #f0f6ff; }
  .stat-box .lbl { font-size: 9px; color: #666; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
  .stat-box .val { font-size: 13px; font-weight: 800; color: #1a73e8; margin-top: 3px; }

  /* Judul seksi */
  .section-head {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 8px;
  }
  .section-head h3 { font-size: 13px; color: #1a1a2e; }
  .total-tag {
    background: #1a73e8; color: #fff;
    font-size: 11px; font-weight: 700;
    padding: 4px 12px; border-radius: 20px;
  }

  /* Tabel */
  table { width: 100%; border-collapse: collapse; font-size: 11px; }
  thead th {
    background: #1a73e8; color: #fff;
    padding: 8px 10px; text-align: left;
    font-size: 10px; text-transform: uppercase; letter-spacing: .4px;
  }
  thead th.r { text-align: right; }
  tbody td   { padding: 7px 10px; border-bottom: 1px solid #eef0f7; }
  tbody td.r { text-align: right; font-weight: 700; }
  tbody td.ok{ color: #1e8c3a; font-weight: 700; }
  tbody tr:nth-child(even) td { background: #f8f9ff; }
  tfoot td {
    padding: 8px 10px; background: #1a73e8; color: #fff;
    font-weight: 800; border-top: 2px solid #1558b0;
  }
  tfoot td.r { text-align: right; }

  /* Footer */
  .doc-footer {
    margin-top: 28px; padding-top: 10px; border-top: 1px solid #dde3f0;
    display: flex; justify-content: space-between;
    font-size: 9px; color: #aaa;
  }

  /* Print overrides */
  @media print {
    .print-bar { display: none !important; }
    body { padding: 0; }
    @page { size: A4 portrait; margin: 14mm; }
    tr { page-break-inside: avoid; }
  }
</style>
</head>
<body>

<div class="print-bar">
  <a href="laporan.php?view=<?= $view ?>" class="btn-back">&#8592; Kembali</a>
  <button class="btn-print" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
</div>

<div class="doc-header">
  <div class="brand">2 PAKSI</div>
  <div class="subtitle"><?= $title ?></div>
  <div class="meta">Dicetak pada: <?= date('d F Y, H:i') ?> WIB</div>
</div>

<div class="stats-row">
  <div class="stat-box <?= $view=='hari'  ? 'active' : '' ?>">
    <div class="lbl">Pendapatan Hari Ini</div>
    <div class="val">Rp <?= number_format($total_hari,  0, ',', '.') ?></div>
  </div>
  <div class="stat-box <?= $view=='bulan' ? 'active' : '' ?>">
    <div class="lbl">Pendapatan Bulan Ini</div>
    <div class="val">Rp <?= number_format($total_bulan, 0, ',', '.') ?></div>
  </div>
  <div class="stat-box <?= $view=='tahun' ? 'active' : '' ?>">
    <div class="lbl">Pendapatan Tahun Ini</div>
    <div class="val">Rp <?= number_format($total_tahun, 0, ',', '.') ?></div>
  </div>
</div>

<div class="section-head">
  <h3><?= $title ?></h3>
  <span class="total-tag">Total: Rp <?= number_format($display_total, 0, ',', '.') ?></span>
</div>

<table>
  <thead>
  <?php if ($view == 'bulan'): ?>
    <tr><th>Bulan</th><th>Jumlah TRX</th><th class="r">Pendapatan</th></tr>
  <?php elseif ($view == 'tahun'): ?>
    <tr><th>Tahun</th><th>Jumlah TRX</th><th class="r">Pendapatan</th></tr>
  <?php else: ?>
    <tr><th>ID Transaksi</th><th>Waktu</th><th>Status</th><th class="r">Jumlah</th></tr>
  <?php endif; ?>
  </thead>

  <tbody>
  <?php while ($row = mysqli_fetch_assoc($res)):
    if ($view == 'bulan'):
      $nm = date('F', mktime(0,0,0,$row['bln'],10)); ?>
    <tr>
      <td><?= $nm ?></td>
      <td><?= $row['jml'] ?> TRX</td>
      <td class="r">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
    </tr>
  <?php elseif ($view == 'tahun'): ?>
    <tr>
      <td><?= $row['thn'] ?></td>
      <td><?= $row['jml'] ?> TRX</td>
      <td class="r">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
    </tr>
  <?php else: ?>
    <tr>
      <td>#TRX_<?= $row['id_transaksi'] ?></td>
      <td><?= date('d M Y H:i', strtotime($row['tanggal_transaksi'])) ?></td>
      <td class="ok">Berhasil</td>
      <td class="r">Rp <?= number_format($row['total_pendapatan'], 0, ',', '.') ?></td>
    </tr>
  <?php endif; endwhile; ?>
  </tbody>

  <tfoot>
  <?php if ($view != 'hari'): ?>
    <tr><td colspan="2">TOTAL KESELURUHAN</td><td class="r">Rp <?= number_format($display_total, 0, ',', '.') ?></td></tr>
  <?php else: ?>
    <tr><td colspan="3">TOTAL KESELURUHAN</td><td class="r">Rp <?= number_format($display_total, 0, ',', '.') ?></td></tr>
  <?php endif; ?>
  </tfoot>
</table>

<div class="doc-footer">
  <span>2 Paksi Point of Sale System</span>
  <span>Laporan dihasilkan otomatis &copy; <?= date('Y') ?></span>
</div>

</body>
</html>
