<?php
require_once 'koneksi.php'; 

// --- PROSES SIMPAN / UPDATE DATA PRODUK ---
if (isset($_POST['save'])) {
    $id_produk     = $_POST['id_produk'];
    $nama_produk   = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
    $stok          = (int)$_POST['stok'];
    $batas_minimal = (int)$_POST['batas_minimal'];
    $harga_jual    = (int)$_POST['harga_jual'];
    $jenis_produk  = mysqli_real_escape_string($koneksi, $_POST['jenis_produk']);
    $modal_beli    = (int)($_POST['modal'] ?? 0);
    $biaya_prod    = (int)($_POST['biaya_produksi'] ?? 0);
    $hpp           = 0;

    // Perhitungan Otomatis: HPP untuk Produksi, Modal untuk Luar
    if ($jenis_produk === 'Luar') { 
        $biaya_prod = 0; 
        $hpp = 0; 
        // modal_beli tetap sesuai inputan
    } else { 
        $modal_beli = 0; 
        // Hitung HPP Otomatis = Total Biaya Produksi / Stok
        if ($stok > 0) {
            $hpp = ceil($biaya_prod / $stok); 
        }
    }

    if (empty($id_produk)) {
        $query = "INSERT INTO produk (nama_produk, harga_satuan, stok, batas_minimal, jenis_produk, modal, biaya_produksi, hpp) 
                  VALUES ('$nama_produk', '$harga_jual', '$stok', '$batas_minimal', '$jenis_produk', '$modal_beli', '$biaya_prod', '$hpp')";
    } else {
        $query = "UPDATE produk SET nama_produk = '$nama_produk', harga_satuan = '$harga_jual', stok = '$stok', batas_minimal = '$batas_minimal', jenis_produk = '$jenis_produk', modal = '$modal_beli', biaya_produksi = '$biaya_prod', hpp = '$hpp' WHERE id_produk = '$id_produk'";
    }
    
    if (mysqli_query($koneksi, $query)) { 
        echo "<script>alert('Data tersimpan!'); window.location='stok.php';</script>"; 
        exit;
    } else {
        echo "<script>alert('Gagal menyimpan data: " . mysqli_error($koneksi) . "');</script>";
    }
}

// --- PROSES LAPOR BARANG RUSAK ---
if (isset($_POST['lapor_rusak'])) {
    $id_produk    = mysqli_real_escape_string($koneksi, $_POST['id_produk_rusak']);
    $jumlah_rusak = (int)$_POST['jumlah_rusak'];
    $keterangan   = mysqli_real_escape_string($koneksi, $_POST['keterangan_rusak']);
    $stok_awal    = (int)$_POST['stok_awal'];
    $tanggal_rusak = date('Y-m-d'); // Ambil tanggal hari ini
    
    if ($jumlah_rusak > $stok_awal) {
        echo "<script>alert('Gagal: Jumlah rusak melebihi stok yang ada!'); window.history.back();</script>";
        exit;
    } else {
        // Ambil data HPP / Modal terkini dari produk tersebut
        $cek_harga = mysqli_query($koneksi, "SELECT jenis_produk, modal, hpp FROM produk WHERE id_produk = '$id_produk'");
        $data_harga = mysqli_fetch_assoc($cek_harga);
        
        // Tentukan harga yang dipakai sebagai patokan kerugian
        $harga_satuan = ($data_harga['jenis_produk'] == 'Luar') ? $data_harga['modal'] : $data_harga['hpp'];
        
        // Hitung total nilai kerugian
        $nilai_kerugian = $jumlah_rusak * $harga_satuan;

        // Kurangi stok di tabel produk
        $query_rusak = "UPDATE produk SET 
                        stok = stok - $jumlah_rusak, 
                        stok_rusak = stok_rusak + $jumlah_rusak,
                        keterangan_rusak = CONCAT(IFNULL(keterangan_rusak, ''), ' | ', '$keterangan')
                        WHERE id_produk = '$id_produk'";
                        
        if (mysqli_query($koneksi, $query_rusak)) {
            // Masukkan histori ke tabel riwayat_kerugian
            mysqli_query($koneksi, "INSERT INTO riwayat_kerugian (id_produk, jumlah_rusak, nilai_kerugian, tanggal, keterangan) 
                                    VALUES ('$id_produk', '$jumlah_rusak', '$nilai_kerugian', '$tanggal_rusak', '$keterangan')");
                                    
            echo "<script>alert('Laporan barang rusak berhasil dicatat! Stok dikurangi & Kerugian dihitung.'); window.location='stok.php';</script>";
            exit;
        } else {
            echo "<script>alert('Gagal memproses database: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
            exit;
        }
    }
}

// --- PROSES HAPUS ---
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    mysqli_query($koneksi, "DELETE FROM produk WHERE id_produk = '$id'");
    echo "<script>alert('Data Berhasil Dihapus'); window.location='stok.php';</script>";
    exit;
}

// --- PROSES TAMBAH STOK CEPAT (MOVING AVERAGE UNTUK HPP DAN MODAL) ---
if (isset($_POST['proses_tambah_cepat'])) {
    $id = mysqli_real_escape_string($koneksi, $_POST['id_produk_cepat']);
    $jumlah_tambah = (int)$_POST['jumlah_tambah'];
    $biaya_batch_baru = (int)$_POST['biaya_batch_baru']; // Total tagihan kulakan ATAU total biaya produksi

    // Ambil data produk saat ini
    $cek = mysqli_query($koneksi, "SELECT stok, jenis_produk, modal, hpp FROM produk WHERE id_produk = '$id'");
    $data = mysqli_fetch_assoc($cek);

    $stok_lama = (int)$data['stok'];
    $jenis = $data['jenis_produk'];
    $stok_baru_total = $stok_lama + $jumlah_tambah;

    if ($jenis == 'Produksi') {
        $hpp_lama = (int)$data['hpp'];
        // Rumus Moving Average untuk HPP (Produksi Sendiri)
        $total_nilai_lama = $stok_lama * $hpp_lama;
        $hpp_baru = ceil(($total_nilai_lama + $biaya_batch_baru) / $stok_baru_total);
        
        $sql = "UPDATE produk SET stok = $stok_baru_total, hpp = $hpp_baru WHERE id_produk = '$id'";
    } else { 
        $modal_lama = (int)$data['modal'];
        // Rumus Moving Average untuk Modal (Produk Luar)
        $total_nilai_lama = $stok_lama * $modal_lama;
        $modal_baru = ceil(($total_nilai_lama + $biaya_batch_baru) / $stok_baru_total);
        
        $sql = "UPDATE produk SET stok = $stok_baru_total, modal = $modal_baru WHERE id_produk = '$id'";
    }
    
    if (mysqli_query($koneksi, $sql)) {
        echo "<script>alert('Stok dan Harga Modal/HPP berhasil diperbarui!'); window.location='stok.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal menambah stok!');</script>";
    }
}
// =======================================================
// BATAS LOGIKA PHP, MULAI HTML DI BAWAH INI
// =======================================================
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Stok Barang | 2 Paksi</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="stok.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet" />
    <style>
        .header-actions { display: flex; flex-direction: column; gap: 10px; align-items: flex-end; }
        .filter-grup { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .filter-grup select, .filter-grup input { padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-family: inherit; }
        .btn-filter { padding: 8px 15px; background: var(--cokelat-muda, #d4a373); color: white; border: none; border-radius: 6px; cursor: pointer; }
        .btn-filter:hover { opacity: 0.9; }
        .baris-atas { display: flex; justify-content: space-between; width: 100%; align-items: center; }
        textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-family: inherit; resize: vertical; }
    </style>
</head>
<body>
    <input type="checkbox" id="check-menu">
    <div class="bar-atas-mobile">
        <div class="nama-toko">2 PAKSI</div>
        <label for="check-menu" class="tombol-buka">
            <i class="fa-solid fa-bars"></i>
        </label>
    </div>

    <aside class="menu-samping">
        <div class="bagian-atas">
            <div class="judul-logo">2 PAKSI</div>
            <nav class="daftar-menu">
                <a href="dashboard.php" class="link-menu"><i class="fa-solid fa-house"></i> Beranda</a>
                <a href="kasir.php" class="link-menu"><i class="fa-solid fa-cash-register"></i> Kasir</a>
                <a href="stok.php" class="link-menu aktif"><i class="fa-solid fa-box"></i> Stok Barang</a>
                <a href="laporan.php" class="link-menu"><i class="fa-solid fa-file-lines"></i> Laporan</a>
                <a href="pengaturan.php" class="link-menu"><i class="fa-solid fa-gear"></i> Pengaturan</a>
            </nav>
        </div>
        <div class="bagian-bawah">
            <a href="logout.php" class="link-menu keluar"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a>
        </div>
    </aside>

    <main class="isi-halaman">
        <header class="header-halaman">
            <div class="baris-atas">
                <h1>Manajemen Stok</h1>
                <button class="btn-tambah" type="button" onclick="bukaModal('tambah')">
                    <i class="fa-solid fa-plus"></i> Tambah Produk
                </button>
            </div>
            
            <div class="header-actions">
                <form action="stok.php" method="GET" class="filter-grup">
                    <input type="text" name="cari" placeholder="Cari nama produk..." value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '' ?>">
                    <select name="jenis">
                        <option value="">Semua Jenis</option>
                        <option value="Luar" <?= (isset($_GET['jenis']) && $_GET['jenis'] == 'Luar') ? 'selected' : '' ?>>Produk Luar</option>
                        <option value="Produksi" <?= (isset($_GET['jenis']) && $_GET['jenis'] == 'Produksi') ? 'selected' : '' ?>>Produksi Sendiri</option>
                    </select>
                    <select name="status">
                        <option value="">Semua Status Stok</option>
                        <option value="aman" <?= (isset($_GET['status']) && $_GET['status'] == 'aman') ? 'selected' : '' ?>>Stok Aman</option>
                        <option value="kritis" <?= (isset($_GET['status']) && $_GET['status'] == 'kritis') ? 'selected' : '' ?>>Hampir Habis</option>
                        <option value="habis" <?= (isset($_GET['status']) && $_GET['status'] == 'habis') ? 'selected' : '' ?>>Stok Habis</option>
                    </select>
                    <select name="sort">
                        <option value="stok_asc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'stok_asc') ? 'selected' : '' ?>>Stok: Paling Sedikit</option>
                        <option value="stok_desc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'stok_desc') ? 'selected' : '' ?>>Stok: Paling Banyak</option>
                        <option value="nama_asc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'nama_asc') ? 'selected' : '' ?>>Nama: A - Z</option>
                    </select>
                    <button type="submit" class="btn-filter"><i class="fa-solid fa-filter"></i></button>
                    <a href="stok.php" class="btn-filter" style="background: #e74c3c; text-decoration: none;"><i class="fa-solid fa-rotate-right"></i></a>
                </form>
            </div>
        </header>

        <div class="tabel-wadah">
            <table>
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Jenis</th>
                        <th>Harga Jual</th>
                        <th>Stok Saat Ini</th>
                        <th style="text-align: center;">Rusak</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $kondisi = [];
                    if (!empty($_GET['cari'])) { $kondisi[] = "nama_produk LIKE '%" . mysqli_real_escape_string($koneksi, $_GET['cari']) . "%'"; }
                    if (!empty($_GET['jenis'])) { $kondisi[] = "jenis_produk = '" . mysqli_real_escape_string($koneksi, $_GET['jenis']) . "'"; }
                    if (!empty($_GET['status'])) {
                        if ($_GET['status'] == 'habis') { $kondisi[] = "stok <= 0"; }
                        elseif ($_GET['status'] == 'kritis') { $kondisi[] = "(stok > 0 AND stok <= batas_minimal)"; }
                        elseif ($_GET['status'] == 'aman') { $kondisi[] = "stok > batas_minimal"; }
                    }

                    $sql_where = count($kondisi) > 0 ? " WHERE " . implode(" AND ", $kondisi) : "";
                    
                    $sql_order = " ORDER BY stok ASC"; 
                    if (!empty($_GET['sort'])) {
                        switch ($_GET['sort']) {
                            case 'stok_desc': $sql_order = " ORDER BY stok DESC"; break;
                            case 'nama_asc':  $sql_order = " ORDER BY nama_produk ASC"; break;
                        }
                    }

                    $sql = "SELECT * FROM produk" . $sql_where . $sql_order;
                    $res = mysqli_query($koneksi, $sql);

                    if(mysqli_num_rows($res) == 0):
                    ?>
                        <tr><td colspan="6" style="text-align: center; padding: 20px;">Data tidak ditemukan.</td></tr>
                    <?php endif; ?>

                    <?php while ($row = mysqli_fetch_assoc($res)):
                        $stok = $row['stok'];
                        $batas = $row['batas_minimal'];
                        $rusak = isset($row['stok_rusak']) ? $row['stok_rusak'] : 0;
                        $jenis_p = isset($row['jenis_produk']) ? $row['jenis_produk'] : 'Luar';
                        
                        if ($stok <= 0) { $class = "stok-habis"; $label = "Habis"; }
                        elseif ($stok <= $batas) { $class = "stok-kritis"; $label = "Kritis"; }
                        else { $class = "stok-aman"; $label = "Aman"; }
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['nama_produk']) ?></strong></td>
                        <td><span style="font-size: 0.85em; padding: 3px 8px; background: #eee; border-radius: 4px;"><?= $jenis_p ?></span></td>
                        <td><strong>Rp <?= number_format($row['harga_satuan'], 0, ',', '.') ?></strong></td>
                        <td>
                            <span class="badge-stok <?= $class ?>"><?= $stok ?> - <?= $label ?></span>
                        </td>
                        <td style="text-align: center; color: #e74c3c; font-weight: bold;">
                            <?= $rusak > 0 ? $rusak : '-' ?>
                        </td>
                        <td style="text-align: center; display: flex; gap: 12px; justify-content: center; align-items: center;">
                             <i class="fa-solid fa-cart-plus" title="Tambah Stok Masuk" style="color: #27ae60; cursor:pointer; font-size: 1.1em;"
                                onclick="bukaModalStokCepat('<?= $row['id_produk'] ?>', '<?= addslashes($row['nama_produk']) ?>')">
                             </i>

                             <i class="fa-solid fa-triangle-exclamation" title="Lapor Barang Rusak" style="color: #e74c3c; cursor:pointer; font-size: 1.1em;"
                                onclick="bukaModalRusak('<?= $row['id_produk'] ?>', '<?= addslashes($row['nama_produk']) ?>', '<?= $stok ?>')">
                             </i>
                             
                             <i class="fa-regular fa-pen-to-square aksi-edit" title="Edit Produk" style="color: var(--cokelat-tua); cursor:pointer;"
                                onclick="bukaModal('edit', '<?= $row['id_produk'] ?>', '<?= addslashes($row['nama_produk']) ?>', '<?= $row['stok'] ?>', '<?= $row['harga_satuan'] ?>', '<?= $row['batas_minimal'] ?>', '<?= $jenis_p ?>', '<?= $row['modal']??0 ?>', '<?= $row['biaya_produksi']??0 ?>')">
                             </i>
                             
                             <a href="stok.php?hapus=<?= $row['id_produk'] ?>" onclick="return confirm('Hapus produk <?= addslashes($row['nama_produk']) ?>?')" class="tombol-hapus-teks">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal-bg" id="modalProduk">
        <div class="modal-konten" style="max-height: 90vh; overflow-y: auto;">
            <div class="modal-head">
                <h3 id="mTitle">Tambah Produk</h3>
                <i class="fa-solid fa-xmark" style="cursor: pointer" onclick="tutupModal('modalProduk')"></i>
            </div>
            <form action="stok.php" method="POST" class="modal-body">
                <input type="hidden" name="id_produk" id="mId">
                
                <div class="input-grup">
                    <label>Nama Produk</label>
                    <input type="text" name="nama_produk" id="mNama" required>
                </div>
                <div class="input-grup">
                    <label>Jenis Produk</label>
                    <select name="jenis_produk" id="mJenis" onchange="ubahTampilanForm()" required>
                        <option value="Luar">Produk Luar</option>
                        <option value="Produksi">Produksi Sendiri</option>
                    </select>
                </div>
                
                <div id="formLuar" class="input-grup">
                    <label>Modal / Harga Beli Satuan (Rp)</label>
                    <input type="number" name="modal" id="mModal" value="0">
                </div>
                
                <div id="formProduksi" style="display: none; background: #f9f9f9; padding: 10px; border-radius: 6px; margin-bottom: 15px;">
                    <div class="input-grup" style="margin-bottom: 0;">
                        <label>Total Biaya Produksi (Rp)</label>
                        <input type="number" name="biaya_produksi" id="mBiaya" value="0">
                        <small style="color: #888; margin-top: 5px; display: block;">*HPP per satuan akan dihitung otomatis berdasarkan jumlah stok.</small>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px">
                    <div class="input-grup">
                        <label>Stok Saat Ini</label>
                        <input type="number" name="stok" id="mStok" required>
                    </div>
                    <div class="input-grup">
                        <label>Batas Minimal Stok</label>
                        <input type="number" name="batas_minimal" id="mBatas" required>
                    </div>
                </div>

                <div class="input-grup">
                    <label>Harga Jual (Rp)</label>
                    <input type="number" name="harga_jual" id="mHarga" required>
                </div>
                <button type="submit" name="save" class="btn-simpan" id="mBtn">Simpan Data</button>
            </form>
        </div>
    </div>

    <div class="modal-bg" id="modalRusak">
        <div class="modal-konten">
            <div class="modal-head">
                <h3 style="color: #e74c3c;"><i class="fa-solid fa-triangle-exclamation"></i> Lapor Barang Rusak</h3>
                <i class="fa-solid fa-xmark" style="cursor: pointer" onclick="tutupModal('modalRusak')"></i>
            </div>
            <form action="stok.php" method="POST" class="modal-body">
                <input type="hidden" name="id_produk_rusak" id="rId">
                <input type="hidden" name="stok_awal" id="rStokAwal">
                
                <p style="margin-bottom: 15px; font-size: 0.9em; color: #555;">
                    Produk: <strong id="rNamaProduk" style="color: #000;"></strong><br>
                    Stok Tersedia: <strong id="rStokTeks"></strong>
                </p>

                <div class="input-grup">
                    <label>Jumlah Barang Rusak</label>
                    <input type="number" name="jumlah_rusak" id="rJumlah" min="1" required>
                    <small style="color: #888;">Stok akan otomatis dikurangi sejumlah ini.</small>
                </div>

                <div class="input-grup">
                    <label>Keterangan / Penyebab (Opsional)</label>
                    <textarea name="keterangan_rusak" rows="3" placeholder="Contoh: Jatuh saat di gudang, basi, dll..."></textarea>
                </div>
                
                <button type="submit" name="lapor_rusak" class="btn-simpan" style="background: #e74c3c;">Proses Laporan</button>
            </form>
        </div>
    </div>

    <div class="modal-bg" id="modalStokCepat">
        <div class="modal-konten">
            <div class="modal-head">
                <h3>Tambah Stok Masuk (Batch Baru)</h3>
                <i class="fa-solid fa-xmark" style="cursor: pointer" onclick="tutupModal('modalStokCepat')"></i>
            </div>
            <form action="stok.php" method="POST" class="modal-body">
                <input type="hidden" name="id_produk_cepat" id="cId">
                
                <p style="margin-bottom: 15px; font-size: 0.9em;">
                    Produk: <strong id="cNamaProduk"></strong>
                </p>

                <div class="input-grup">
                    <label>Jumlah Stok yang Ditambahkan</label>
                    <input type="number" name="jumlah_tambah" required min="1">
                </div>

                <div class="input-grup">
                    <label>Total Biaya / Modal Tagihan (Rp)</label>
                    <input type="number" name="biaya_batch_baru" required min="0">
                    <small style="color: #888; display: block; margin-top: 5px;">
                        Masukkan <strong>TOTAL</strong> biaya kulakan nota ini (untuk Produk Luar) ATAU total biaya produksi (untuk Produk Dalam). Sistem akan menghitung rata-rata Modal/HPP otomatis.
                    </small>
                </div>
                
                <button type="submit" name="proses_tambah_cepat" class="btn-simpan" style="background: #27ae60;">Update Stok & Harga</button>
            </form>
        </div>
    </div>

    <script>
        const modalUtama = document.getElementById('modalProduk');
        const modalRusak = document.getElementById('modalRusak');
        const modalStokCepat = document.getElementById('modalStokCepat');
        
        function ubahTampilanForm() {
            const jenis = document.getElementById('mJenis').value;
            if (jenis === 'Luar') {
                document.getElementById('formLuar').style.display = 'block';
                document.getElementById('formProduksi').style.display = 'none';
            } else {
                document.getElementById('formLuar').style.display = 'none';
                document.getElementById('formProduksi').style.display = 'block';
            }
        }

        // Fungsi buka modal edit/tambah
        function bukaModal(mode, id = '', nama = '', stok = '', harga = '', batas = '10', jenis = 'Luar', modalVal = '0', biayaVal = '0') {
            modalUtama.style.display = 'flex';
            document.getElementById('mId').value = id;
            document.getElementById('mNama').value = nama;
            document.getElementById('mStok').value = stok;
            document.getElementById('mBatas').value = batas; 
            document.getElementById('mHarga').value = harga;
            document.getElementById('mJenis').value = jenis;
            document.getElementById('mModal').value = modalVal;
            document.getElementById('mBiaya').value = biayaVal;
            ubahTampilanForm(); 
            document.getElementById('mTitle').innerText = mode === 'edit' ? 'Edit Produk' : 'Tambah Produk Baru';
            document.getElementById('mBtn').innerText = mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Produk';
        }

        function bukaModalRusak(id, nama, stokSekarang) {
            modalRusak.style.display = 'flex';
            document.getElementById('rId').value = id;
            document.getElementById('rStokAwal').value = stokSekarang;
            document.getElementById('rNamaProduk').innerText = nama;
            document.getElementById('rStokTeks').innerText = stokSekarang;
            document.getElementById('rJumlah').max = stokSekarang; 
        }

        function bukaModalStokCepat(id, nama) {
            modalStokCepat.style.display = 'flex';
            document.getElementById('cId').value = id;
            document.getElementById('cNamaProduk').innerText = nama;
        }

        function tutupModal(idModal) { document.getElementById(idModal).style.display = 'none'; }
        
        window.onclick = function(event) { 
            if (event.target == modalUtama) tutupModal('modalProduk'); 
            if (event.target == modalRusak) tutupModal('modalRusak'); 
            if (event.target == modalStokCepat) tutupModal('modalStokCepat');
        }
    </script>
</body>
</html>