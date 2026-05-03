<?php
include 'koneksi.php';
include 'tgl_indo.php';
session_start();

if($_SESSION['status'] != "login") header("location:login.php");

$hari_ini = date("Y-m-d");

// ==========================================
// AUTO-CREATE TABEL CLOSING SHIFT (JAGA-JAGA JIKA BELUM ADA)
// ==========================================
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS closing_shift (
    id_closing INT AUTO_INCREMENT PRIMARY KEY,
    waktu_closing DATETIME,
    laba_hari_ini INT,
    saldo_sistem INT,
    saldo_fisik INT,
    selisih INT,
    catatan TEXT
)");

// ==========================================
// FASE 5: HITUNG LABA BERSIH HARI INI
// ==========================================
$q_laba = mysqli_query($koneksi, "
    SELECT SUM(d.subtotal - (p.hpp * d.jumlah_produk)) as laba_kotor
    FROM detail_transaksi d
    JOIN transaksi t ON d.id_transaksi = t.id_transaksi
    JOIN produk p ON d.id_produk = p.id_produk
    WHERE DATE(t.tanggal_transaksi) = '$hari_ini'
");
$d_laba = mysqli_fetch_assoc($q_laba);
$laba_hari_ini = $d_laba['laba_kotor'] ?? 0;

// Menghitung Saldo Kas Sistem
$m = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(nominal) as t FROM buku_kas WHERE jenis='Pemasukan'"))['t'] ?? 0;
$k = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(nominal) as t FROM buku_kas WHERE jenis='Pengeluaran'"))['t'] ?? 0;
$saldo_sistem = $m - $k;

// ==========================================
// 1. LOGIKA BUKU KAS (Pemasukan/Pengeluaran)
// ==========================================
if(isset($_POST['simpan_kas'])) {
    $jenis = $_POST['jenis'];
    $ket = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    $nom = (int)str_replace('.', '', $_POST['nominal']); 
    $tgl = date("Y-m-d H:i:s");

    $is_pribadi = stripos($ket, 'prive') !== false || stripos($ket, 'ambil') !== false || stripos($ket, 'jajan') !== false;
    
    if($jenis == 'Pengeluaran' && $is_pribadi && $nom > $laba_hari_ini) {
        $_SESSION['warning_kanibal'] = "⚠️ STOP! Anda mengambil Rp ".number_format($nom)." padahal untung hari ini cuma Rp ".number_format($laba_hari_ini).". Anda memakan uang MODAL!";
    }

    mysqli_query($koneksi, "INSERT INTO buku_kas VALUES (NULL, '$tgl', '$ket', '$jenis', '$nom')");
    header("location:buku_kas.php?tab=kas");
    exit();
}

if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM buku_kas WHERE id_buku_kas = '$id'");
    header("location:buku_kas.php?tab=kas");
    exit();
}

// ==========================================
// 2. LOGIKA PIUTANG (Pelunasan Kasbon)
// ==========================================
if(isset($_POST['bayar_hutang'])) {
    $id_trx = $_POST['id_transaksi'];
    $jumlah_bayar = (int)str_replace('.', '', $_POST['jumlah_bayar']);
    $tgl_sekarang = date("Y-m-d H:i:s");

    $cek = mysqli_query($koneksi, "SELECT * FROM transaksi WHERE id_transaksi = '$id_trx'");
    $d = mysqli_fetch_assoc($cek);
    $nama = $d['nama_pelanggan'];
    $total_tagihan = $d['total_pendapatan'];
    $sudah_bayar_lama = $d['uang_diterima'];
    
    $bayar_baru = $sudah_bayar_lama + $jumlah_bayar;
    $sisa_baru = $total_tagihan - $bayar_baru;
    $status_baru = ($sisa_baru <= 0) ? 'Lunas' : 'Kasbon';
    if($sisa_baru < 0) $sisa_baru = 0; 

    mysqli_query($koneksi, "UPDATE transaksi SET uang_diterima = '$bayar_baru', kurang_bayar = '$sisa_baru', status_bayar = '$status_baru' WHERE id_transaksi = '$id_trx'");

    $ket_kas = "Bayar Hutang: $nama (TRX #$id_trx)";
    mysqli_query($koneksi, "INSERT INTO buku_kas (tanggal, keterangan, jenis, nominal) VALUES ('$tgl_sekarang', '$ket_kas', 'Pemasukan', '$jumlah_bayar')");

    echo "<script>alert('Pembayaran berhasil dicatat!'); window.location='buku_kas.php?tab=piutang';</script>";
}

// ==========================================
// 3. LOGIKA TUTUP BUKU (CLOSING SHIFT)
// ==========================================
if(isset($_POST['simpan_closing'])) {
    $fisik = (int)str_replace('.', '', $_POST['saldo_fisik']);
    $sistem = $saldo_sistem; // Ambil dari perhitungan di atas
    $laba = $laba_hari_ini;
    $selisih = $fisik - $sistem;
    $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan']);
    $waktu = date("Y-m-d H:i:s");

    mysqli_query($koneksi, "INSERT INTO closing_shift VALUES (NULL, '$waktu', '$laba', '$sistem', '$fisik', '$selisih', '$catatan')");
    echo "<script>alert('Tutup Kasir Berhasil Dicatat!'); window.location='buku_kas.php?tab=closing';</script>";
    exit();
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'kas';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Keuangan - 2 PAKSI</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* Styling Tabs & Alert */
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto; white-space: nowrap; padding-bottom: 5px; }
        .tab-btn { padding: 12px 25px; border-radius: 10px; background: white; color: #666; font-weight: 700; text-decoration: none; border: 1px solid #ddd; transition: 0.3s; }
        .tab-btn.active { background: var(--cokelat-tua); color: white; border-color: var(--cokelat-tua); }
        .tab-btn:hover:not(.active) { background: #f1f2f6; }
        
        .badge-hutang { background: #ffebee; color: #e74c3c; padding: 5px 10px; border-radius: 5px; font-weight: bold; font-size: 0.85em; }
        .btn-bayar-mini { background: #27ae60; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 0.8em; }
        .badge-pemasukan { background: #e8f8f5; color: #27ae60; padding: 5px 10px; border-radius: 5px; font-weight: bold; font-size: 0.85em; }
        .badge-pengeluaran { background: #ffebee; color: #e74c3c; padding: 5px 10px; border-radius: 5px; font-weight: bold; font-size: 0.85em; }
        
        .modal-bayar { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 25px; border-radius: 15px; width: 350px; }
        .input-kasir { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-weight: bold; box-sizing: border-box; margin-bottom: 15px; }

        .warning-kanibal { background: #e74c3c; color: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: bold; animation: blink 1s infinite; }
        @keyframes blink { 0% {opacity: 1;} 50% {opacity: 0.8;} 100% {opacity: 1;} }
        .box-laba { background: #e8f8f5; border: 1px dashed #2ecc71; padding: 15px; border-radius: 10px; }
        
        /* Layout Grid Fix */
        .layout-dua-kolom { display: grid; grid-template-columns: 350px 1fr; gap: 20px; }
        @media (max-width: 768px) { .layout-dua-kolom { grid-template-columns: 1fr; } }

        /* Khusus Closing */
        .box-selisih { padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 15px; font-weight: bold; font-size: 1.2em; background: #f9f9f9; border: 2px dashed #ccc; }
        .selisih-pas { background: #e8f8f5; border-color: #27ae60; color: #27ae60; }
        .selisih-kurang { background: #ffebee; border-color: #e74c3c; color: #e74c3c; }
        .selisih-lebih { background: #e3f2fd; border-color: #3498db; color: #3498db; }
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
            <a href="buku_kas.php" class="link-menu aktif"><i class="fa-solid fa-wallet"></i> Buku Kas & Piutang</a>
            <a href="laporan.php" class="link-menu"><i class="fa-solid fa-file-lines"></i> Laporan</a>
        </nav>
    </div>
    <div class="bagian-bawah">
        <a href="logout.php" class="link-menu keluar"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a>
    </div>
</aside>

<main class="isi-halaman">
    <header class="judul-halaman" style="margin-bottom: 20px;">
        <h1>Keuangan Toko</h1>
        <p>Kelola arus kas, piutang, dan tutup buku harian.</p>
    </header>

    <?php if(isset($_SESSION['warning_kanibal'])): ?>
        <div class="warning-kanibal">
            <?= $_SESSION['warning_kanibal']; ?>
            <?php unset($_SESSION['warning_kanibal']); ?>
        </div>
    <?php endif; ?>

    <div class="tabs">
        <a href="?tab=kas" class="tab-btn <?= $tab == 'kas' ? 'active' : '' ?>"><i class="fa-solid fa-book"></i> Riwayat Kas</a>
        <a href="?tab=piutang" class="tab-btn <?= $tab == 'piutang' ? 'active' : '' ?>"><i class="fa-solid fa-hand-holding-dollar"></i> Buku Piutang (Kasbon)</a>
        <a href="?tab=closing" class="tab-btn <?= $tab == 'closing' ? 'active' : '' ?>"><i class="fa-solid fa-lock"></i> Tutup Kasir (Shift)</a>
    </div>

    <?php if($tab == 'kas'): ?>
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
        <div class="box-laba">
            <small style="color: #27ae60; font-weight: bold;"><i class="fa-solid fa-chart-line"></i> UNTUNG BERSIH HARI INI</small>
            <h2 style="color: #2ecc71; margin: 5px 0;">Rp <?= number_format($laba_hari_ini, 0, ',', '.') ?></h2>
            <small style="color: #666;">*Uang aman yang bisa Anda pakai (Prive).</small>
        </div>
        <div style="background: var(--cokelat-tua); color: white; padding: 20px; border-radius: 15px;">
            <p style="font-weight: bold; margin: 0; color: #ddd;"><i class="fa-solid fa-cash-register"></i> TOTAL SALDO LACI (Sistem)</p>
            <h2 style="margin: 5px 0;">Rp <?= number_format($saldo_sistem, 0, ',', '.') ?></h2>
            <small style="color: #aaa;">*Termasuk Modal. Jangan kanibal modal!</small>
        </div>
    </div>

    <div class="layout-dua-kolom">
        <div class="kotak-putih" style="height: fit-content;">
            <h3 class="judul-sub" style="margin-top: 0;">Catat Transaksi</h3>
            <form method="POST">
                <label style="font-size: 0.85em; font-weight: bold; display: block; margin-bottom: 5px;">Jenis</label>
                <select name="jenis" class="input-kasir">
                    <option value="Pemasukan">Pemasukan (Termasuk Modal Awal)</option>
                    <option value="Pengeluaran">Pengeluaran (Belanja/Prive)</option>
                </select>

                <label style="font-size: 0.85em; font-weight: bold; display: block; margin-bottom: 5px;">Keterangan</label>
                <input type="text" name="keterangan" class="input-kasir" required placeholder="Cth: Prive, Modal Awal, Beli Kantong">

                <label style="font-size: 0.85em; font-weight: bold; display: block; margin-bottom: 5px;">Nominal (Rp)</label>
                <input type="text" name="nominal" class="input-kasir" onkeyup="formatRp(this)" required>

                <button type="submit" name="simpan_kas" style="width: 100%; padding: 12px; background: var(--cokelat-tua); color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Simpan Data</button>
            </form>
        </div>

        <div class="kotak-putih">
            <h3 class="judul-sub" style="margin-top: 0;">Riwayat Kas Terakhir</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #eee; text-align: left;">
                        <th style="padding: 10px;">Tanggal</th>
                        <th style="padding: 10px;">Keterangan</th>
                        <th style="padding: 10px;">Jenis</th>
                        <th style="padding: 10px; text-align: right;">Nominal</th>
                        <th style="padding: 10px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $q = mysqli_query($koneksi, "SELECT * FROM buku_kas ORDER BY tanggal DESC LIMIT 50");
                    while($row = mysqli_fetch_assoc($q)){
                        $isM = $row['jenis'] == 'Pemasukan';
                    ?>
                    <tr style="border-bottom: 1px solid #f5f5f5;">
                        <td style="padding: 10px;"><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
                        <td style="padding: 10px;"><strong><?= htmlspecialchars($row['keterangan']) ?></strong></td>
                        <td style="padding: 10px;"><span class="<?= $isM ? 'badge-pemasukan' : 'badge-pengeluaran' ?>"><?= $row['jenis'] ?></span></td>
                        <td style="padding: 10px; text-align: right; color: <?= $isM ? '#27ae60' : '#e74c3c' ?>; font-weight: bold;">
                            <?= $isM ? '+' : '-' ?> Rp <?= number_format($row['nominal'], 0, ',', '.') ?>
                        </td>
                        <td style="padding: 10px; text-align: center;">
                            <a href="?hapus=<?= $row['id_buku_kas'] ?>" onclick="return confirm('Hapus data ini?')" style="color: #ccc;"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php elseif($tab == 'piutang'): ?>
    <div class="kotak-putih">
        <h3 class="judul-sub" style="margin-top: 0;"><i class="fa-solid fa-list-ul"></i> Daftar Transaksi Belum Lunas (Kasbon)</h3>
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <thead>
                <tr style="border-bottom: 2px solid #eee; text-align: left;">
                    <th style="padding: 10px;">Tgl Transaksi</th>
                    <th style="padding: 10px;">Pelanggan</th>
                    <th style="padding: 10px;">Total Nota</th>
                    <th style="padding: 10px;">Sisa Hutang</th>
                    <th style="padding: 10px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $q = mysqli_query($koneksi, "SELECT * FROM transaksi WHERE status_bayar = 'Kasbon' ORDER BY tanggal_transaksi DESC");
                if(mysqli_num_rows($q) > 0){
                    while($row = mysqli_fetch_assoc($q)){
                ?>
                <tr style="border-bottom: 1px solid #f5f5f5;">
                    <td style="padding: 10px;"><?= date('d/m/Y H:i', strtotime($row['tanggal_transaksi'])) ?></td>
                    <td style="padding: 10px;"><strong><?= htmlspecialchars($row['nama_pelanggan']) ?></strong></td>
                    <td style="padding: 10px;">Rp <?= number_format($row['total_pendapatan'], 0, ',', '.') ?></td>
                    <td style="padding: 10px;"><span class="badge-hutang">Rp <?= number_format($row['kurang_bayar'], 0, ',', '.') ?></span></td>
                    <td style="padding: 10px;">
                        <button class="btn-bayar-mini" onclick="bukaModal('<?= $row['id_transaksi'] ?>', '<?= $row['nama_pelanggan'] ?>', '<?= $row['kurang_bayar'] ?>')">
                            <i class="fa-solid fa-money-bill-wave"></i> Bayar
                        </button>
                    </td>
                </tr>
                <?php 
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:#888;'>Semua tagihan lunas. Mantap! ✅</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <?php elseif($tab == 'closing'): ?>
    <div class="layout-dua-kolom">
        <div class="kotak-putih" style="height: fit-content; background: #fffdfa; border: 2px solid var(--cokelat-muda);">
            <h3 class="judul-sub" style="margin-top: 0; color: var(--cokelat-tua);"><i class="fa-solid fa-calculator"></i> Form Tutup Kasir</h3>
            <p style="font-size: 0.85em; color: #666; margin-bottom: 15px;">Hitung uang fisik di laci Anda, lalu bandingkan dengan catatan sistem.</p>
            
            <form method="POST">
                <label style="font-size: 0.85em; font-weight: bold; display: block; margin-bottom: 5px;">Saldo Catatan Sistem (Otomatis)</label>
                <input type="text" class="input-kasir" value="Rp <?= number_format($saldo_sistem, 0, ',', '.') ?>" readonly style="background: #f1f2f6; cursor: not-allowed; color: #888;">

                <label style="font-size: 0.85em; font-weight: bold; display: block; margin-bottom: 5px;">Uang Fisik Laci (Hitung Manual)</label>
                <input type="text" name="saldo_fisik" id="fisikLaci" class="input-kasir" onkeyup="hitungSelisih()" required placeholder="Masukkan jumlah uang nyata di laci...">

                <div id="boxSelisih" class="box-selisih">Masukkan nominal fisik...</div>

                <label style="font-size: 0.85em; font-weight: bold; display: block; margin-bottom: 5px;">Catatan (Opsional)</label>
                <textarea name="catatan" class="input-kasir" rows="2" placeholder="Cth: Uang Rp 2000 sobek 1 lembar..."></textarea>

                <button type="submit" name="simpan_closing" style="width: 100%; padding: 12px; background: #e67e22; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1.1em;">
                    <i class="fa-solid fa-lock"></i> Kunci & Simpan Tutup Buku
                </button>
            </form>
        </div>

        <div class="kotak-putih">
            <h3 class="judul-sub" style="margin-top: 0;"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Tutup Buku</h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #eee; text-align: left; font-size: 0.9em;">
                            <th style="padding: 10px;">Waktu Closing</th>
                            <th style="padding: 10px; text-align: right;">Laba Hari Itu</th>
                            <th style="padding: 10px; text-align: right;">Sistem</th>
                            <th style="padding: 10px; text-align: right;">Fisik</th>
                            <th style="padding: 10px; text-align: center;">Status Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $qc = mysqli_query($koneksi, "SELECT * FROM closing_shift ORDER BY waktu_closing DESC LIMIT 30");
                        if(mysqli_num_rows($qc) > 0){
                            while($rc = mysqli_fetch_assoc($qc)){
                                $slsh = $rc['selisih'];
                                if($slsh == 0) { $bg = "badge-pemasukan"; $teks = "PAS (Rp 0)"; }
                                elseif($slsh < 0) { $bg = "badge-pengeluaran"; $teks = "MINUS (Rp ".number_format(abs($slsh),0,',','.').")"; }
                                else { $bg = "badge-pemasukan"; $teks = "LEBIH (Rp ".number_format($slsh,0,',','.').")"; $bg="background:#e3f2fd; color:#3498db; padding: 5px 10px; border-radius: 5px; font-weight: bold; font-size: 0.85em;"; }
                        ?>
                        <tr style="border-bottom: 1px solid #f5f5f5; font-size: 0.9em;">
                            <td style="padding: 10px;"><?= date('d/m/Y H:i', strtotime($rc['waktu_closing'])) ?></td>
                            <td style="padding: 10px; text-align: right; color:#27ae60;">Rp <?= number_format($rc['laba_hari_ini'], 0, ',', '.') ?></td>
                            <td style="padding: 10px; text-align: right; color:#888;">Rp <?= number_format($rc['saldo_sistem'], 0, ',', '.') ?></td>
                            <td style="padding: 10px; text-align: right; font-weight:bold;">Rp <?= number_format($rc['saldo_fisik'], 0, ',', '.') ?></td>
                            <td style="padding: 10px; text-align: center;">
                                <span class="<?= $slsh == 0 || $slsh < 0 ? $bg : '' ?>" style="<?= $slsh > 0 ? $bg : '' ?>"><?= $teks ?></span>
                                <?php if($rc['catatan'] != '') echo "<br><small style='color:#999; font-style:italic;'>'".$rc['catatan']."'</small>"; ?>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:#888;'>Belum ada data riwayat tutup buku.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<div id="modalBayar" class="modal-bayar">
    <div class="modal-content">
        <h3 style="margin-top:0;">Pelunasan Hutang</h3>
        <p id="teksInfo" style="font-size:0.9em; color:#666;"></p>
        <form method="POST">
            <input type="hidden" name="id_transaksi" id="mId">
            <label style="display:block; font-size:0.8em; font-weight:bold; margin-bottom:5px;">Jumlah Bayar (Rp)</label>
            <input type="text" name="jumlah_bayar" id="mJumlah" class="input-kasir" onkeyup="formatRp(this)" required>
            
            <div style="display:flex; gap:10px;">
                <button type="submit" name="bayar_hutang" style="flex:1; background:#27ae60; color:white; border:none; padding:12px; border-radius:5px; cursor:pointer; font-weight:bold;">Simpan</button>
                <button type="button" onclick="tutupModal()" style="flex:1; background:#ccc; border:none; border-radius:5px; cursor:pointer;">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
// Format Rupiah standar
function formatRp(obj){
    let val = obj.value.replace(/[^,\d]/g, "").toString();
    let split = val.split(",");
    let sisa = split[0].length % 3;
    let idr = split[0].substr(0, sisa);
    let ribu = split[0].substr(sisa).match(/\d{3}/gi);
    if(ribu){
        let separator = sisa ? "." : "";
        idr += separator + ribu.join(".");
    }
    obj.value = idr;
}

// Menghilangkan titik dari string rupiah untuk kalkulasi JS
function unformatRp(str) {
    return parseInt(str.replace(/\./g, '')) || 0;
}

// Logika Hitung Selisih Otomatis di Tab Closing
function hitungSelisih() {
    let inputFisik = document.getElementById('fisikLaci');
    if (!inputFisik) return; // Mencegah error jika bukan di tab closing

    formatRp(inputFisik); // Format angka saat diketik

    let saldoSistem = <?= $saldo_sistem ?>; 
    let saldoFisik = unformatRp(inputFisik.value);
    let selisih = saldoFisik - saldoSistem;
    
    let box = document.getElementById('boxSelisih');

    if (inputFisik.value === "") {
        box.className = 'box-selisih';
        box.innerHTML = 'Masukkan nominal fisik...';
        return;
    }

    if (selisih === 0) {
        box.className = 'box-selisih selisih-pas';
        box.innerHTML = '✅ BALANCE (Uang Pas)';
    } else if (selisih < 0) {
        box.className = 'box-selisih selisih-kurang';
        box.innerHTML = '⚠️ MINUS: Rp ' + Math.abs(selisih).toLocaleString('id-ID');
    } else {
        box.className = 'box-selisih selisih-lebih';
        box.innerHTML = '💰 LEBIH: Rp ' + selisih.toLocaleString('id-ID');
    }
}

// Logika Modal Piutang
function bukaModal(id, nama, sisa){
    document.getElementById('mId').value = id;
    document.getElementById('teksInfo').innerText = "Pelanggan: " + nama + " (Sisa: Rp " + parseInt(sisa).toLocaleString('id-ID') + ")";
    document.getElementById('mJumlah').value = "";
    document.getElementById('modalBayar').style.display = 'flex';
}
function tutupModal(){
    document.getElementById('modalBayar').style.display = 'none';
}
</script>

</body>
</html>