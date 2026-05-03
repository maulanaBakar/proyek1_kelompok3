<?php
include 'koneksi.php';
include 'tgl_indo.php';
session_start();

if($_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login");
    exit();
}

if(!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// Logika Tambah Keranjang
if(isset($_GET['aksi']) && $_GET['aksi'] == "tambah") {
    $id = $_GET['id_produk'];
    $data = mysqli_query($koneksi, "SELECT * FROM produk WHERE id_produk='$id'");
    $p = mysqli_fetch_assoc($data);

    if($p) {
        if(isset($_SESSION['keranjang'][$id])) {
            if ($_SESSION['keranjang'][$id]['qty'] + 1 <= $p['stok']) {
                $_SESSION['keranjang'][$id]['qty'] += 1;
            } else {
                echo "<script>alert('Stok tidak mencukupi!'); window.location='kasir.php';</script>";
                exit();
            }
        } else {
            $_SESSION['keranjang'][$id] = [
                'nama'   => $p['nama_produk'],
                'harga'  => $p['harga_satuan'],
                'qty'    => 1,
                'diskon' => $p['diskon'] ?? 0
            ];
        }
    }
    header("location:kasir.php"); 
    exit();
}

// Logika Update Qty
if(isset($_POST['update_qty'])) {
    $id = $_POST['id_produk'];
    $qty = (int)$_POST['qty'];
    
    if($qty <= 0) {
        unset($_SESSION['keranjang'][$id]);
    } else {
        $data = mysqli_query($koneksi, "SELECT stok FROM produk WHERE id_produk='$id'");
        $p = mysqli_fetch_assoc($data);

        if($qty > $p['stok']) {
            echo "<script>alert('Qty melebihi stok! Maksimal: ".$p['stok']."'); window.location='kasir.php';</script>";
            exit();
        } else {
            $_SESSION['keranjang'][$id]['qty'] = $qty;
        }
    }
    header("location:kasir.php");
    exit();
}

// Logika Hapus Item
if(isset($_GET['aksi']) && $_GET['aksi'] == "hapus") {
    $id = $_GET['id_produk'];
    unset($_SESSION['keranjang'][$id]);
    header("location:kasir.php");
    exit();
}

// =========================================================================
// FASE 2: LOGIKA PROSES BAYAR (DISKON, KASBON, BUKU KAS)
// =========================================================================
if(isset($_POST['proses_bayar'])) {
    if(!empty($_SESSION['keranjang'])) {
        
        $total_awal    = (int)$_POST['total_awal'];
        $diskon_global = (int)($_POST['diskon_global'] ?? 0);
        
        // Hitung total setelah diskon
        $total_akhir = $total_awal - $diskon_global;
        if($total_akhir < 0) $total_akhir = 0;

        // Logika Uang Pas Otomatis
        $uang_diterima_raw = $_POST['uang_diterima'] ?? '';
        if(trim($uang_diterima_raw) === '') {
            $uang_diterima = $total_akhir; // Jika input tidak diisi, anggap uangnya PAS
        } else {
            $uang_diterima = (int)str_replace('.', '', $uang_diterima_raw); 
        }

        $kurang_bayar = 0;
        $status_transaksi = 'Lunas';

        // Deteksi apakah uangnya kurang (Skenario Kasbon)
        if($uang_diterima < $total_akhir) {
            $kurang_bayar = $total_akhir - $uang_diterima;
            $status_transaksi = 'Pending';
        }

        $tgl = date("Y-m-d H:i:s");

        // Masukkan ke tabel transaksi
        mysqli_query($koneksi, "INSERT INTO transaksi (tanggal_transaksi, total_pendapatan, status_transaksi, diskon_global, kurang_bayar) 
                                VALUES ('$tgl', '$total_akhir', '$status_transaksi', '$diskon_global', '$kurang_bayar')");
        $id_transaksi = mysqli_insert_id($koneksi);

        foreach($_SESSION['keranjang'] as $id_produk => $item) {
            $qty = $item['qty'];
            $diskon = $item['diskon'] ?? 0;
            $harga_final = $item['harga'] - ($item['harga'] * $diskon / 100);
            $subtotal = $harga_final * $qty;

            $cek_p = mysqli_query($koneksi, "SELECT jenis_produk, modal, hpp FROM produk WHERE id_produk = '$id_produk'");
            $data_p = mysqli_fetch_assoc($cek_p);
            
            $modal_terkini = ($data_p['jenis_produk'] == 'Luar') ? $data_p['modal'] : $data_p['hpp'];

            mysqli_query($koneksi, "INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah_produk, subtotal, modal_satuan) 
                                   VALUES ('$id_transaksi', '$id_produk', '$qty', '$subtotal', '$modal_terkini')");

            mysqli_query($koneksi, "UPDATE produk SET stok = stok - $qty WHERE id_produk = '$id_produk'");
        }

        // =========================================================
        // PENCATATAN OTOMATIS KE BUKU KAS
        // =========================================================
        $uang_masuk_kas = ($uang_diterima < $total_akhir) ? $uang_diterima : $total_akhir;

        if ($uang_masuk_kas > 0) {
            $keterangan = "Pendapatan Transaksi #" . $id_transaksi;
            mysqli_query($koneksi, "INSERT INTO buku_kas (tanggal, keterangan, jenis, nominal) 
                                    VALUES ('$tgl', '$keterangan', 'Pemasukan', '$uang_masuk_kas')");
        }
        // =========================================================

        unset($_SESSION['keranjang']);
        
        // Munculkan notifikasi dinamis
        if($status_transaksi == 'Pending') {
            echo "<script>alert('Transaksi Kasbon Disimpan! Kurang Bayar: Rp ".number_format($kurang_bayar,0,',','.')."'); window.location='kasir.php';</script>";
        } else {
            echo "<script>alert('Pembayaran Lunas Berhasil!'); window.location='kasir.php';</script>";
        }
        exit();
    }
}

// =========================================================================
// QUERY PENCARIAN 
// =========================================================================
$cari = isset($_GET['cari']) ? trim(mysqli_real_escape_string($koneksi, $_GET['cari'])) : '';
$urut = isset($_GET['urut']) ? $_GET['urut'] : 'default';

$sql = "SELECT * FROM produk WHERE stok > 0 AND status_aktif = 'Y'";
if ($cari != "") {
    $sql .= " AND (nama_produk LIKE '%$cari%')";
}

if ($urut == "stok_kecil") {
    $sql .= " ORDER BY CAST(stok AS UNSIGNED) ASC";
} elseif ($urut == "stok_besar") {
    $sql .= " ORDER BY CAST(stok AS UNSIGNED) DESC";
} else {
    $sql .= " ORDER BY id_produk DESC";
}

$tanggal_indo = hari_indo(date("D")) . ', ' . tgl_full(date("Y-m-d"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kasir - 2 PAKSI</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="dashboard.css">
    <style>
        :root { --primary: #4a3e3d; --accent: #d6c4b0; --bg-body: #fdfbf7; --text-main: #2d2424; --green: #27ae60; --red: #e74c3c; }
        .main-grid { display: grid; grid-template-columns: 1fr 400px; gap: 25px; align-items: start; }
        .produk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
        .card-produk { background: white; padding: 25px 20px; border-radius: 15px; text-align: center; text-decoration: none; color: inherit; border: 1px solid #f1f2f6; transition: 0.3s; }
        .card-produk:hover { border-color: var(--accent); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .stok-info { font-size: 0.8rem; font-weight: 600; margin-top: 15px; color: #e67e22; background: #fff5eb; padding: 6px 15px; border-radius: 50px; display: block; width: fit-content; margin-left: auto; margin-right: auto; }
        
        .cart-panel { background: white; border-radius: 15px; border: 1px solid #f1f2f6; position: sticky; top: 20px; overflow: hidden; }
        .cart-header { background: var(--primary); color: white; padding: 15px; text-align: center; font-weight: 600; }
        .cart-items { max-height: 400px; overflow-y: auto; padding: 15px; }
        
        .qty-control { display: flex; align-items: center; background: #f8f9fa; border-radius: 25px; padding: 2px 8px; border: 1px solid #eee; }
        .qty-input { width: 45px; border: none; background: transparent; text-align: center; font-weight: bold; outline: none; }
        .qty-control button { border: none; background: transparent; font-size: 1.2rem; cursor: pointer; padding: 0 8px; color: var(--primary); }
        
        .cart-footer { padding: 20px; background: #fafafa; border-top: 1px solid #eee; }
        
        /* TOMBOL BAYAR UTAMA */
        .btn-bayar { width: 100%; padding: 14px; border-radius: 10px; border: none; background: var(--green); color: white; font-weight: 800; cursor: pointer; font-size: 1.1rem; margin-top: 5px; transition: 0.2s;}
        .btn-bayar:hover { background: #219653; }
        .btn-bayar:disabled { background: #ccc; cursor: not-allowed; }

        /* TOGGLE OPSI KASBON/DISKON */
        .btn-opsi { width: 100%; padding: 10px; border-radius: 8px; border: 1px dashed #ccc; background: #fff; color: #666; font-weight: 600; cursor: pointer; font-size: 0.9rem; transition: 0.2s; margin-bottom: 10px;}
        .btn-opsi:hover { background: #f1f2f6; color: var(--primary); border-color: var(--primary); }
        
        /* PANEL FORM OPSI */
        .panel-opsi { background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 15px; margin-bottom: 15px; display: none; }
        .input-kasir { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-weight: bold; font-family: inherit; box-sizing: border-box; transition: 0.3s;}
        .input-kasir:focus { border-color: var(--accent); outline: none; }
        
        .status-box { padding: 10px; border-radius: 8px; text-align: center; font-weight: bold; font-size: 0.9em; margin-top: 10px; transition: 0.3s; }

        @media (max-width: 1100px) { .main-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <input type="checkbox" id="check-menu">
    <div class="bar-atas-mobile">
        <div class="nama-toko">2 PAKSI</div>
        <label for="check-menu" class="tombol-buka"><i class="fa-solid fa-bars"></i></label>
    </div>

    <aside class="menu-samping">
        <div class="bagian-atas">
            <div class="judul-logo">2 PAKSI</div>
            <nav class="daftar-menu">
                <a href="dashboard.php" class="link-menu"><i class="fa-solid fa-house"></i> Beranda</a>
                <a href="kasir.php" class="link-menu aktif"><i class="fa-solid fa-cash-register"></i> Kasir</a>
                <a href="stok.php" class="link-menu"><i class="fa-solid fa-box"></i> Stok Barang</a>
                <a href="buku_kas.php" class="link-menu"><i class="fa-solid fa-wallet"></i> Buku Kas</a>
                <a href="laporan.php" class="link-menu"><i class="fa-solid fa-file-lines"></i> Laporan</a>
            </nav>
        </div>
        <div class="bagian-bawah">
            <a href="logout.php" class="link-menu keluar"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a>
        </div>
    </aside>

    <main class="isi-halaman">
        <header class="judul-halaman">
            <h1>Kasir Penjualan</h1>
            <p style="color: #888;"><?= $tanggal_indo ?></p>
        </header>

        <div class="main-grid">
            <div class="produk-section">
                <form action="" method="GET" style="display:flex; gap:10px; margin-bottom:20px;">
                    <input type="text" name="cari" class="search-input" placeholder="Cari produk..." value="<?= htmlspecialchars($cari) ?>" style="flex:1; padding:12px; border-radius:10px; border:1px solid #ddd;">
                    <button type="submit" class="btn-bayar" style="width:auto; padding:0 25px; margin-top:0; background:var(--primary);">Cari</button>
                </form>

                <div class="produk-grid">
                    <?php 
                    $res = mysqli_query($koneksi, $sql);
                    while($p = mysqli_fetch_assoc($res)): 
                    ?>
                    <a href="?aksi=tambah&id_produk=<?= $p['id_produk'] ?>" class="card-produk">
                        <h3 style="margin-bottom:10px; font-weight: 800; font-size: 1.1rem;"><?= htmlspecialchars($p['nama_produk']) ?></h3>
                        <span style="font-weight:800; color:var(--primary); font-size: 1rem;">Rp <?= number_format($p['harga_satuan'], 0, ',', '.') ?></span>
                        <span class="stok-info">Stok: <?= $p['stok'] ?></span>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="cart-panel">
                <div class="cart-header">Keranjang Kasir</div>
                <div class="cart-items">
                    <?php 
                    $total = 0;
                    foreach($_SESSION['keranjang'] as $id => $item): 
                        $harga_final = $item['harga'] - ($item['harga'] * ($item['diskon'] ?? 0) / 100);
                        $sub = $harga_final * $item['qty'];
                        $total += $sub;
                    ?>
                        <div style="border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:15px;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                                <span style="font-weight:600;"><?= htmlspecialchars($item['nama']) ?></span>
                                <span style="font-weight:800;">Rp <?= number_format($sub, 0, ',', '.') ?></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <form method="POST" class="qty-control">
                                    <input type="hidden" name="id_produk" value="<?= $id ?>">
                                    <button type="button" onclick="let q = this.nextElementSibling; if(parseInt(q.value) > 1){ q.stepDown(); this.form.submit(); } else { if(confirm('Hapus item?')){ q.value=0; this.form.submit(); } }">-</button>
                                    <input type="number" name="qty" class="qty-input" value="<?= $item['qty'] ?>" onchange="this.form.submit()">
                                    <button type="button" onclick="this.previousElementSibling.stepUp(); this.form.submit();">+</button>
                                    <input type="hidden" name="update_qty" value="1">
                                </form>
                                <a href="?aksi=hapus&id_produk=<?= $id ?>" style="color:var(--red);"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if(empty($_SESSION['keranjang'])) echo "<p style='text-align:center; color:#ccc; margin-top:20px;'><i class='fa-solid fa-basket-shopping fa-2x mb-2'></i><br>Keranjang Kosong</p>"; ?>
                </div>

                <div class="cart-footer">
                    <form method="POST" id="formPembayaran" onsubmit="bersihkanInputSebelumKirim()">
                        <input type="hidden" name="total_awal" id="totalAwal" value="<?= $total ?>">
                        
                        <div style="display:flex; justify-content:space-between; margin-bottom:15px; font-weight:800; font-size:1.4rem; color: var(--primary);">
                            <span>TOTAL</span>
                            <span id="teksTotalAkhir">Rp <?= number_format($total, 0, ',', '.') ?></span>
                        </div>

                        <button type="button" class="btn-opsi" id="btnToggle" onclick="toggleOpsi()" <?= ($total == 0) ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-chevron-down"></i> Klik jika ada Diskon / Uang Pelanggan tidak pas
                        </button>

                        <div id="panelOpsi" class="panel-opsi">
                            <div style="margin-bottom: 10px;">
                                <label style="font-size: 0.85em; font-weight: 700; color: #888; display: block; margin-bottom: 5px;">Diskon Global (Rp)</label>
                                <input type="text" name="diskon_global" id="diskonGlobal" class="input-kasir" value="" placeholder="Cth: 5.000" onkeyup="formatDanHitung(this)">
                            </div>

                            <div style="margin-bottom: 5px;">
                                <label style="font-size: 0.85em; font-weight: 700; color: #888; display: block; margin-bottom: 5px;">Uang Diterima (Rp)</label>
                                <input type="text" name="uang_diterima" id="uangDiterima" class="input-kasir" value="" placeholder="Kosongkan jika uang pas" onkeyup="formatDanHitung(this)" style="border-color: var(--green); color: var(--green);">
                            </div>

                            <div id="boxStatus" class="status-box" style="background: #e8f8f5; color: var(--green);">
                                ✅ Status: Uang Pas
                            </div>
                        </div>

                        <button type="submit" name="proses_bayar" class="btn-bayar" <?= ($total == 0) ? 'disabled' : '' ?>>
                            Proses Pembayaran
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        let formTerbuka = false;

        // Fungsi Membuka/Menutup Form Opsi
        function toggleOpsi() {
            let panel = document.getElementById('panelOpsi');
            let btn = document.getElementById('btnToggle');
            
            formTerbuka = !formTerbuka;
            
            if (formTerbuka) {
                panel.style.display = 'block';
                btn.innerHTML = '<i class="fa-solid fa-chevron-up"></i> Tutup Form Opsi';
                document.getElementById('uangDiterima').focus();
            } else {
                panel.style.display = 'none';
                btn.innerHTML = '<i class="fa-solid fa-chevron-down"></i> Klik jika ada Diskon / Uang Pelanggan tidak pas';
                // Reset isian jika ditutup
                document.getElementById('diskonGlobal').value = '';
                document.getElementById('uangDiterima').value = '';
                kalkulasiUang();
            }
        }

        // Membersihkan angka dari text (untuk perhitungan)
        function unformatRupiah(angka) { 
            if(angka === "") return "";
            return parseInt(angka.replace(/[^0-9]/g, '')) || 0; 
        }

        // Memberi titik otomatis (Format IDR)
        function formatRupiah(angka) {
            let number_string = angka.replace(/[^0-9]/g, '').toString();
            let sisa = number_string.length % 3;
            let rupiah = number_string.substr(0, sisa);
            let ribuan = number_string.substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                let separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }
            return rupiah;
        }

        function formatDanHitung(input) {
            input.value = formatRupiah(input.value);
            kalkulasiUang();
        }

        function kalkulasiUang() {
            let totalAwal = parseInt(document.getElementById('totalAwal').value) || 0;
            let valDiskon = document.getElementById('diskonGlobal').value;
            let valUang = document.getElementById('uangDiterima').value;

            let diskon = unformatRupiah(valDiskon) || 0;
            
            // Hitung Total Setelah Diskon
            let totalAkhir = totalAwal - diskon;
            if (totalAkhir < 0) totalAkhir = 0;

            document.getElementById('teksTotalAkhir').innerText = 'Rp ' + totalAkhir.toLocaleString('id-ID');

            let boxStatus = document.getElementById('boxStatus');
            
            // Jika kolom uang kosong, anggap uang pas
            if (valUang === "") {
                boxStatus.style.background = '#e8f8f5';
                boxStatus.style.color = 'var(--green)';
                boxStatus.innerHTML = '✅ Status: Uang Pas';
                return;
            }

            let uangDiterima = unformatRupiah(valUang);
            let kembalian = uangDiterima - totalAkhir;

            if (kembalian < 0) {
                boxStatus.style.background = '#ffebee';
                boxStatus.style.color = 'var(--red)';
                boxStatus.innerHTML = '⚠️ KASBON / KURANG: Rp ' + Math.abs(kembalian).toLocaleString('id-ID');
            } else {
                boxStatus.style.background = '#e8f8f5';
                boxStatus.style.color = 'var(--green)';
                boxStatus.innerHTML = '✅ KEMBALIAN: Rp ' + kembalian.toLocaleString('id-ID');
            }
        }

        // Mencegah error input ke database
        function bersihkanInputSebelumKirim() {
            let d = document.getElementById('diskonGlobal');
            let u = document.getElementById('uangDiterima');
            d.value = unformatRupiah(d.value);
            // Hanya bersihkan uang jika ada isinya, biarkan kosong jika memang uang pas
            if(u.value !== "") {
                u.value = unformatRupiah(u.value);
            }
        }
    </script>
</body>
</html>