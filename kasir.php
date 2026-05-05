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
// Inisialisasi Session untuk Hold Transaksi
if(!isset($_SESSION['hold_keranjang'])) {
    $_SESSION['hold_keranjang'] = [];
}

// ==========================================
// LOGIKA KERANJANG BELANJA
// ==========================================
if(isset($_GET['aksi'])) {
    $aksi = $_GET['aksi'];
    $id = $_GET['id_produk'] ?? '';

    if($aksi == "tambah") {
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
                    'modal'  => ($p['jenis_produk'] == 'Luar') ? $p['modal'] : $p['hpp'] // Untuk cek rugi
                ];
            }
        }
        header("location:kasir.php"); exit;
    }
    
    elseif($aksi == "plus") {
        $data = mysqli_query($koneksi, "SELECT stok FROM produk WHERE id_produk='$id'");
        $p = mysqli_fetch_assoc($data);
        if ($_SESSION['keranjang'][$id]['qty'] + 1 <= $p['stok']) {
            $_SESSION['keranjang'][$id]['qty'] += 1;
        } else {
            echo "<script>alert('Stok mentok bro!'); window.location='kasir.php';</script>";
            exit;
        }
        header("location:kasir.php"); exit;
    }
    
    elseif($aksi == "min") {
        if($_SESSION['keranjang'][$id]['qty'] > 1) {
            $_SESSION['keranjang'][$id]['qty'] -= 1;
        } else {
            unset($_SESSION['keranjang'][$id]);
        }
        header("location:kasir.php"); exit;
    }
    
    elseif($aksi == "hapus") {
        unset($_SESSION['keranjang'][$id]);
        header("location:kasir.php"); exit;
    }
    
    elseif($aksi == "clear") {
        $_SESSION['keranjang'] = [];
        header("location:kasir.php"); exit;
    }

    // ==========================================
    // LOGIKA HOLD (TAHAN) & LOAD TRANSAKSI
    // ==========================================
    elseif($aksi == "load_hold") {
        $id_hold = $_GET['id_hold'];
        if(isset($_SESSION['hold_keranjang'][$id_hold])) {
            // Kosongkan keranjang saat ini, ganti dengan yang di-hold
            $_SESSION['keranjang'] = $_SESSION['hold_keranjang'][$id_hold]['keranjang'];
            unset($_SESSION['hold_keranjang'][$id_hold]); // Hapus dari daftar hold
            echo "<script>alert('Berhasil! Transaksi dilanjutkan.'); window.location='kasir.php';</script>";
            exit;
        }
    }
    
    elseif($aksi == "hapus_hold") {
        $id_hold = $_GET['id_hold'];
        unset($_SESSION['hold_keranjang'][$id_hold]);
        header("location:kasir.php"); exit;
    }
}

// PROSES SIMPAN KE HOLD
if (isset($_POST['proses_hold'])) {
    if (!empty($_SESSION['keranjang'])) {
        $nama_pelanggan_hold = trim($_POST['nama_pelanggan_hold']);
        if (empty($nama_pelanggan_hold)) $nama_pelanggan_hold = "Tamu " . date('H:i');
        
        $id_hold = time(); // Bikin ID unik pakai waktu
        $_SESSION['hold_keranjang'][$id_hold] = [
            'nama' => $nama_pelanggan_hold,
            'waktu' => date('H:i:s'),
            'keranjang' => $_SESSION['keranjang']
        ];
        
        $_SESSION['keranjang'] = []; // Kosongkan layar kasir
        echo "<script>alert('Sip! Transaksi atas nama $nama_pelanggan_hold ditahan.'); window.location='kasir.php';</script>";
        exit;
    }
}

// ==========================================
// PROSES BAYAR (CHECKOUT)
// ==========================================
if(isset($_POST['bayar'])) {
    if(empty($_SESSION['keranjang'])) {
        echo "<script>alert('Keranjang masih kosong!'); window.location='kasir.php';</script>";
        exit;
    }

    $nama_pelanggan = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
    $diskon_global  = (int)str_replace('.', '', $_POST['diskon_global']);
    $uang_diterima  = (int)str_replace('.', '', $_POST['uang_diterima']);
    $tanggal        = date("Y-m-d H:i:s");

    $total_belanja  = 0;
    foreach($_SESSION['keranjang'] as $item) {
        $total_belanja += ($item['harga'] * $item['qty']);
    }

    $grand_total = $total_belanja - $diskon_global;
    if($grand_total < 0) $grand_total = 0;

    $kembalian = $uang_diterima - $grand_total;
    $status_transaksi = ($kembalian < 0) ? 'Kasbon' : 'Lunas';

    // Insert ke tabel transaksi
    $query_transaksi = "INSERT INTO transaksi (tanggal_transaksi, nama_pelanggan, total_pendapatan, uang_diterima, diskon, status) 
                        VALUES ('$tanggal', '$nama_pelanggan', '$grand_total', '$uang_diterima', '$diskon_global', '$status_transaksi')";
    
    if(mysqli_query($koneksi, $query_transaksi)) {
        $id_transaksi = mysqli_insert_id($koneksi);

        // Insert ke detail_transaksi & kurangi stok
        foreach($_SESSION['keranjang'] as $id_produk => $item) {
            $qty = $item['qty'];
            $subtotal = $item['harga'] * $qty;

            mysqli_query($koneksi, "INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah_produk, subtotal) 
                                    VALUES ('$id_transaksi', '$id_produk', '$qty', '$subtotal')");
            
            // Kurangi stok
            mysqli_query($koneksi, "UPDATE produk SET stok = stok - $qty WHERE id_produk = '$id_produk'");
        }

        $_SESSION['keranjang'] = []; // Bersihkan keranjang
        
        $pesan = ($status_transaksi == 'Kasbon') ? "Transaksi KASBON tersimpan!" : "Pembayaran LUNAS Berhasil!";
        echo "<script>alert('$pesan'); window.open('cetak_struk.php?id=$id_transaksi', '_blank'); window.location='kasir.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal menyimpan transaksi!'); window.location='kasir.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kasir Pintar | 2 Paksi</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        .layout-kasir { display: grid; grid-template-columns: 60% 40%; gap: 20px; height: calc(100vh - 80px); }
        .kiri-produk { background: white; border-radius: 12px; padding: 20px; overflow-y: auto; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .kanan-keranjang { background: white; border-radius: 12px; padding: 20px; display: flex; flex-direction: column; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        .grid-produk { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; margin-top: 15px; }
        .card-produk { border: 1px solid #eee; border-radius: 8px; padding: 15px; text-align: center; cursor: pointer; transition: 0.2s; background: #fff; }
        .card-produk:hover { border-color: var(--cokelat-muda); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(212,163,115,0.2); }
        .card-produk h4 { margin: 0 0 5px 0; font-size: 0.95em; color: #333; }
        .card-produk p { margin: 0; color: var(--cokelat-tua); font-weight: bold; }
        .stok-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 0.75em; margin-top: 8px; background: #e8f8f5; color: #27ae60; }
        .stok-habis { background: #fdedec; color: #e74c3c; cursor: not-allowed; opacity: 0.6; }

        .list-keranjang { flex: 1; overflow-y: auto; border-bottom: 2px dashed #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .item-keranjang { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f9f9f9; }
        .item-info h5 { margin: 0; font-size: 0.95em; }
        .item-info p { margin: 0; font-size: 0.85em; color: #666; }
        .qty-control { display: flex; align-items: center; gap: 10px; }
        .qty-control a { text-decoration: none; width: 25px; height: 25px; display: flex; justify-content: center; align-items: center; background: #f1f1f1; border-radius: 4px; color: #333; font-weight: bold; }
        .qty-control a:hover { background: var(--cokelat-muda); color: white; }

        .total-area { background: #f9f9f9; padding: 15px; border-radius: 8px; }
        .baris-total { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9em; }
        .baris-grand { font-size: 1.3em; font-weight: bold; color: var(--cokelat-tua); border-top: 2px dashed #ccc; padding-top: 10px; margin-top: 5px; }
        
        .input-kasir { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; font-size: 1em; }
        .btn-bayar { width: 100%; padding: 15px; background: #27ae60; color: white; border: none; border-radius: 8px; font-size: 1.1em; font-weight: bold; cursor: pointer; margin-top: 10px; transition: 0.2s;}
        .btn-bayar:hover { background: #219653; }
        
        .box-warning { padding: 10px; border-radius: 6px; text-align: center; font-weight: bold; margin-bottom: 10px; }
        .rugi { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
        .untung { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }

        .aksi-tambahan { display: flex; gap: 10px; margin-top: 10px; }
        .btn-tahan { flex: 1; padding: 10px; background: #f39c12; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; text-align: center; text-decoration: none;}
        .btn-kosong { flex: 1; padding: 10px; background: #e74c3c; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; text-align: center; text-decoration: none;}
        
        .badge-hold { background: #f39c12; color: white; padding: 5px 10px; border-radius: 20px; font-size: 0.8em; text-decoration: none; font-weight: bold; float: right; cursor: pointer;}
        .badge-hold:hover { background: #d68910; }

        /* Modal Styles */
        .modal-bg { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-konten { background: white; padding: 20px; border-radius: 10px; width: 400px; max-width: 90%; }
        .tabel-hold { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabel-hold th, .tabel-hold td { border: 1px solid #ddd; padding: 8px; text-align: left; }
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
                <a href="pengaturan.php" class="link-menu"><i class="fa-solid fa-gear"></i> Pengaturan</a>
            </nav>
        </div>
        <div class="bagian-bawah">
            <a href="logout.php" class="link-menu keluar"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a>
        </div>
    </aside>

    <main class="isi-halaman">
        <div class="layout-kasir">
            
            <div class="kiri-produk">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Pilih Produk</h3>
                    <input type="text" id="cariProduk" placeholder="Cari nama..." style="padding:8px; border-radius:6px; border:1px solid #ccc;">
                </div>
                
                <div class="grid-produk" id="wadahProduk">
                    <?php
                    $q_produk = mysqli_query($koneksi, "SELECT * FROM produk WHERE status_aktif='Y' ORDER BY nama_produk ASC");
                    while($p = mysqli_fetch_assoc($q_produk)):
                        $habis = ($p['stok'] <= 0);
                    ?>
                    <div class="card-produk <?= $habis ? 'stok-habis' : '' ?>" 
                         <?= $habis ? '' : "onclick=\"window.location='kasir.php?aksi=tambah&id_produk={$p['id_produk']}'\"" ?>>
                        <h4><?= htmlspecialchars($p['nama_produk']) ?></h4>
                        <p>Rp <?= number_format($p['harga_satuan'],0,',','.') ?></p>
                        <span class="stok-badge <?= $habis ? 'stok-habis' : '' ?>">Sisa: <?= $p['stok'] ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="kanan-keranjang">
                <h3 style="margin-top: 0;">
                    Keranjang Belanja
                    <?php if(count($_SESSION['hold_keranjang']) > 0): ?>
                        <span class="badge-hold" onclick="bukaModalDaftarHold()">
                            <i class="fa-solid fa-hand-holding"></i> Ada <?= count($_SESSION['hold_keranjang']) ?> Hold
                        </span>
                    <?php endif; ?>
                </h3>
                
                <div class="list-keranjang">
                    <?php 
                    $total = 0;
                    $total_modal = 0; // Untuk cek kerugian
                    if(empty($_SESSION['keranjang'])): ?>
                        <div style="text-align: center; color: #999; margin-top: 50px;">
                            <i class="fa-solid fa-cart-shopping" style="font-size: 3em; margin-bottom: 10px;"></i>
                            <p>Keranjang masih kosong</p>
                        </div>
                    <?php else: 
                        foreach($_SESSION['keranjang'] as $id => $item): 
                            $subtotal = $item['harga'] * $item['qty'];
                            $submodal = $item['modal'] * $item['qty'];
                            $total += $subtotal;
                            $total_modal += $submodal;
                    ?>
                        <div class="item-keranjang">
                            <div class="item-info">
                                <h5><?= $item['nama'] ?></h5>
                                <p>Rp <?= number_format($item['harga'],0,',','.') ?></p>
                            </div>
                            <div class="qty-control">
                                <a href="kasir.php?aksi=min&id_produk=<?= $id ?>">-</a>
                                <span><?= $item['qty'] ?></span>
                                <a href="kasir.php?aksi=plus&id_produk=<?= $id ?>">+</a>
                                <a href="kasir.php?aksi=hapus&id_produk=<?= $id ?>" style="background: #ffcccc; color: #e74c3c;"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </div>
                    <?php 
                        endforeach; 
                    endif; 
                    ?>
                </div>

                <form action="kasir.php" method="POST" id="formKasir">
                    <div class="total-area">
                        <div class="baris-total">
                            <span>Subtotal:</span>
                            <span style="font-weight:bold;">Rp <?= number_format($total,0,',','.') ?></span>
                        </div>
                        <div class="baris-total" style="align-items: center;">
                            <span>Diskon (Rp):</span>
                            <input type="text" name="diskon_global" id="diskonGlobal" value="0" class="input-kasir" style="width: 50%; margin:0; text-align:right;" onkeyup="formatRp(this); hitungKembalian();">
                        </div>
                        
                        <div id="boxWarning" class="box-warning untung" style="display:none;"></div>
                        <input type="hidden" id="totalBelanjaAwal" value="<?= $total ?>">
                        <input type="hidden" id="totalModalHPP" value="<?= $total_modal ?>">

                        <div class="baris-total baris-grand">
                            <span>Total Akhir:</span>
                            <span id="textGrandTotal">Rp <?= number_format($total,0,',','.') ?></span>
                        </div>

                        <input type="text" name="nama_pelanggan" id="namaPelanggan" class="input-kasir" placeholder="Nama Pelanggan (Opsional / Wajib jika Kasbon)">
                        <input type="text" name="uang_diterima" id="uangDiterima" class="input-kasir" placeholder="Uang Diterima (Rp)" onkeyup="formatRp(this); hitungKembalian();">
                        
                        <div id="statusKembalian" style="text-align: center; font-weight: bold; padding: 10px; border-radius: 6px; margin-top: 5px;"></div>
                    </div>

                    <button type="submit" name="bayar" class="btn-bayar" onclick="return bersihkanFormat()">
                        <i class="fa-solid fa-check-circle"></i> Proses Pembayaran
                    </button>
                    
                    <div class="aksi-tambahan">
                        <?php if(!empty($_SESSION['keranjang'])): ?>
                            <button type="button" class="btn-tahan" onclick="bukaModalHold()">
                                <i class="fa-solid fa-pause"></i> Tahan (Hold)
                            </button>
                        <?php endif; ?>
                        <a href="kasir.php?aksi=clear" class="btn-kosong" onclick="return confirm('Yakin kosongkan keranjang?')">
                            <i class="fa-solid fa-trash-can"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <div class="modal-bg" id="modalHold">
        <div class="modal-konten">
            <h3 style="color: #f39c12;"><i class="fa-solid fa-pause"></i> Tahan Transaksi</h3>
            <p style="font-size: 0.9em; color: #555;">Keranjang akan disimpan sementara dan layar kasir akan dikosongkan.</p>
            <form action="kasir.php" method="POST">
                <input type="text" name="nama_pelanggan_hold" class="input-kasir" placeholder="Belanjaan atas nama siapa? (Wajib diisi)" required style="margin-top: 10px;">
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" name="proses_hold" class="btn-tahan" style="width: 100%;">Simpan ke Hold</button>
                    <button type="button" class="btn-kosong" onclick="tutupModal('modalHold')" style="width: 100%;">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-bg" id="modalDaftarHold">
        <div class="modal-konten" style="width: 500px;">
            <h3><i class="fa-solid fa-list"></i> Daftar Transaksi Ditahan</h3>
            <?php if(empty($_SESSION['hold_keranjang'])): ?>
                <p>Tidak ada transaksi yang ditahan saat ini.</p>
            <?php else: ?>
                <table class="tabel-hold">
                    <tr>
                        <th>Waktu</th>
                        <th>Nama Pelanggan</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                    <?php foreach($_SESSION['hold_keranjang'] as $id_hold => $data): ?>
                    <tr>
                        <td><?= $data['waktu'] ?></td>
                        <td><strong><?= htmlspecialchars($data['nama']) ?></strong></td>
                        <td style="text-align: center;">
                            <a href="kasir.php?aksi=load_hold&id_hold=<?= $id_hold ?>" class="stok-badge" style="background: #3498db; color: white; text-decoration: none;">Lanjut</a>
                            <a href="kasir.php?aksi=hapus_hold&id_hold=<?= $id_hold ?>" class="stok-badge" style="background: #e74c3c; color: white; text-decoration: none;" onclick="return confirm('Hapus hold ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
            <button type="button" class="btn-kosong" onclick="tutupModal('modalDaftarHold')" style="width: 100%; margin-top: 15px; background: #95a5a6;">Tutup</button>
        </div>
    </div>

    <script>
        // Pencarian Produk Realtime
        document.getElementById('cariProduk').addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            let cards = document.querySelectorAll('.card-produk');
            cards.forEach(card => {
                let nama = card.querySelector('h4').innerText.toLowerCase();
                if(nama.includes(filter)) card.style.display = '';
                else card.style.display = 'none';
            });
        });

        // Format Uang dan Hitung Kembalian & Rugi
        function formatRp(input) {
            let val = input.value.replace(/[^,\d]/g, '').toString();
            let split = val.split(',');
            let sisa = split[0].length % 3;
            let rupiah = split[0].substr(0, sisa);
            let ribuan = split[0].substr(sisa).match(/\d{3}/gi);
            if(ribuan) {
                let separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }
            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            input.value = rupiah;
        }

        function unformatRupiah(str) {
            return parseInt(str.replace(/[^,\d]/g, '')) || 0;
        }

        let isKasbon = false;
        function hitungKembalian() {
            let d_input = document.getElementById('diskonGlobal').value;
            let u_input = document.getElementById('uangDiterima').value;
            
            let diskon = unformatRupiah(d_input);
            let uang = unformatRupiah(u_input);
            let totalAwal = parseInt(document.getElementById('totalBelanjaAwal').value) || 0;
            let modalHPP = parseInt(document.getElementById('totalModalHPP').value) || 0;
            
            let grandTotal = totalAwal - diskon;
            if(grandTotal < 0) grandTotal = 0;

            document.getElementById('textGrandTotal').innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');

            // --- PERINGATAN RUGI ---
            let boxWarning = document.getElementById('boxWarning');
            if(totalAwal > 0) {
                boxWarning.style.display = 'block';
                let profit = grandTotal - modalHPP;
                if(profit < 0) {
                    boxWarning.className = 'box-warning rugi';
                    boxWarning.innerHTML = '🚨 AWAS RUGI! Potong modal Rp ' + Math.abs(profit).toLocaleString('id-ID');
                } else {
                    boxWarning.className = 'box-warning untung';
                    boxWarning.innerHTML = '✅ Laba Kotor: Rp ' + profit.toLocaleString('id-ID');
                }
            }

            // --- HITUNG KEMBALIAN ---
            let boxStatus = document.getElementById('statusKembalian');
            if (u_input === "") {
                boxStatus.innerHTML = ''; return;
            }

            let kembalian = uang - grandTotal;
            if(kembalian < 0) {
                boxStatus.style.background = '#fdedec';
                boxStatus.style.color = 'var(--red)';
                boxStatus.innerHTML = '⚠️ KASBON: Kurang Rp ' + Math.abs(kembalian).toLocaleString('id-ID');
                isKasbon = true;
            } else {
                boxStatus.style.background = '#e8f8f5';
                boxStatus.style.color = 'var(--green)';
                boxStatus.innerHTML = '✅ KEMBALIAN: Rp ' + kembalian.toLocaleString('id-ID');
                isKasbon = false;
            }
        }

        function bersihkanFormat() {
            let nama = document.getElementById('namaPelanggan').value;
            if(isKasbon && nama.trim() === "") {
                alert("PENTING: Transaksi Kasbon wajib isi Nama Pelanggan!");
                document.getElementById('namaPelanggan').focus();
                return false;
            }

            let d = document.getElementById('diskonGlobal');
            let u = document.getElementById('uangDiterima');
            d.value = unformatRupiah(d.value);
            if(u.value !== "") u.value = unformatRupiah(u.value);
            return true;
        }

        // Script Modals
        const modalHold = document.getElementById('modalHold');
        const modalDaftarHold = document.getElementById('modalDaftarHold');

        function bukaModalHold() { modalHold.style.display = 'flex'; }
        function bukaModalDaftarHold() { modalDaftarHold.style.display = 'flex'; }
        function tutupModal(id) { document.getElementById(id).style.display = 'none'; }
        
        window.onclick = function(e) {
            if (e.target == modalHold) tutupModal('modalHold');
            if (e.target == modalDaftarHold) tutupModal('modalDaftarHold');
        }

        // Jalankan hitungan pertama kali load
        window.onload = hitungKembalian;
    </script>
</body>
</html>