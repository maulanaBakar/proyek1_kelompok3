<?php
require_once 'koneksi.php'; 

// ====================================================================
// AUTO-FIX DATABASE UNTUK RIWAYAT KERUGIAN DAN PRIVE
// ====================================================================
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS riwayat_kerugian (
    id_kerugian INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE,
    id_produk INT,
    jumlah_rusak INT,
    nilai_kerugian INT,
    keterangan TEXT
)");

mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS prive_barang (
    id_prive INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE,
    id_produk INT,
    jumlah INT,
    total_hpp INT,
    keterangan TEXT
)");

// --- PROSES SIMPAN / UPDATE DATA PRODUK ---
if (isset($_POST['save'])) {
    $id_produk     = $_POST['id_produk'];
    $nama_produk   = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
    $stok          = (int)$_POST['stok'];
    $batas_minimal = (int)$_POST['batas_minimal'];
    $harga_jual    = (int)str_replace('.', '', $_POST['harga_jual']);
    $jenis_produk  = mysqli_real_escape_string($koneksi, $_POST['jenis_produk']);
    $modal_beli    = (int)str_replace('.', '', $_POST['modal'] ?? '0');
    $biaya_prod    = (int)str_replace('.', '', $_POST['biaya_produksi'] ?? '0');
    $hpp           = 0;

    // Perhitungan Otomatis: HPP untuk Produksi, Modal untuk Luar
    if ($jenis_produk === 'Luar') { 
        $biaya_prod = 0; 
        $hpp = 0; 
    } else { 
        $modal_beli = 0; 
        if ($stok > 0) {
            $hpp = ceil($biaya_prod / $stok); 
        }
    }

    if ($stok < 0 || $harga_jual < 0 || $modal_beli < 0 || $biaya_prod < 0) {
        echo "<script>alert('Gagal: Nilai stok, harga, atau modal tidak boleh minus/negatif!'); window.history.back();</script>";
        exit;
    }

    if (empty($id_produk)) {
        // INSERT (TAMBAH BARU)
        $query = "INSERT INTO produk (nama_produk, harga_satuan, stok, batas_minimal, jenis_produk, modal, biaya_produksi, hpp) 
                  VALUES ('$nama_produk', '$harga_jual', '$stok', '$batas_minimal', '$jenis_produk', '$modal_beli', '$biaya_prod', '$hpp')";
    } else {
        // UPDATE (EDIT BARANG) - Modal dan HPP sekarang BISA disimpan!
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
    $tanggal_rusak = date('Y-m-d'); 
    
    if ($jumlah_rusak > $stok_awal) {
        echo "<script>alert('Gagal: Jumlah rusak melebihi stok yang ada!'); window.history.back();</script>";
        exit;
    } else {
        $cek_harga = mysqli_query($koneksi, "SELECT jenis_produk, modal, hpp FROM produk WHERE id_produk = '$id_produk'");
        $data_harga = mysqli_fetch_assoc($cek_harga);
        $harga_satuan = ($data_harga['jenis_produk'] == 'Luar') ? $data_harga['modal'] : $data_harga['hpp'];
        $nilai_kerugian = $jumlah_rusak * $harga_satuan;

        $query_rusak = "UPDATE produk SET 
                        stok = stok - $jumlah_rusak, 
                        stok_rusak = stok_rusak + $jumlah_rusak,
                        keterangan_rusak = CONCAT(IFNULL(keterangan_rusak, ''), ' | ', '$keterangan')
                        WHERE id_produk = '$id_produk'";
                        
        if (mysqli_query($koneksi, $query_rusak)) {
            mysqli_query($koneksi, "INSERT INTO riwayat_kerugian (id_produk, jumlah_rusak, nilai_kerugian, tanggal, keterangan) 
                                    VALUES ('$id_produk', '$jumlah_rusak', '$nilai_kerugian', '$tanggal_rusak', '$keterangan')");
            echo "<script>alert('Laporan barang rusak berhasil dicatat! Stok dikurangi & Kerugian dihitung.'); window.location='stok.php';</script>";
            exit;
        }
    }
}

// --- PROSES KOREKSI BARANG RUSAK ---
if (isset($_POST['koreksi_rusak'])) {
    $id_produk      = mysqli_real_escape_string($koneksi, $_POST['id_produk_koreksi']);
    $jumlah_koreksi = (int)$_POST['jumlah_koreksi'];
    $jenis_tindakan = $_POST['jenis_tindakan'];
    $tanggal        = date('Y-m-d');

    $cek = mysqli_query($koneksi, "SELECT stok_rusak, jenis_produk, modal, hpp FROM produk WHERE id_produk = '$id_produk'");
    $data = mysqli_fetch_assoc($cek);

    if ($jumlah_koreksi > $data['stok_rusak']) {
    echo "<script>alert('Gagal: Jumlah melebihi total barang yang rusak!'); window.history.back();</script>";
    exit;
    } elseif ($jumlah_koreksi < 1) { 
    echo "<script>alert('Gagal: Jumlah barang yang dikoreksi tidak boleh nol atau minus!'); window.history.back();</script>";
    exit;
    } else {
        if ($jenis_tindakan == 'kembalikan') {
            $harga_satuan = ($data['jenis_produk'] == 'Luar') ? $data['modal'] : $data['hpp'];
            $nilai_kembali = $jumlah_koreksi * $harga_satuan;

            $query_koreksi = "UPDATE produk SET stok = stok + $jumlah_koreksi, stok_rusak = stok_rusak - $jumlah_koreksi WHERE id_produk = '$id_produk'";
            
            if (mysqli_query($koneksi, $query_koreksi)) {
                mysqli_query($koneksi, "INSERT INTO riwayat_kerugian (id_produk, jumlah_rusak, nilai_kerugian, tanggal, keterangan) 
                                        VALUES ('$id_produk', '-$jumlah_koreksi', '-$nilai_kembali', '$tanggal', 'Koreksi Salah Input Rusak')");
                echo "<script>alert('Koreksi berhasil! Stok telah dikembalikan.'); window.location='stok.php';</script>";
                exit;
            }

        } elseif ($jenis_tindakan == 'buang') {
            $query_buang = "UPDATE produk SET stok_rusak = stok_rusak - $jumlah_koreksi WHERE id_produk = '$id_produk'";
            if (mysqli_query($koneksi, $query_buang)) {
                echo "<script>alert('Sip! Barang rusak sudah dihapus secara permanen dari etalase.'); window.location='stok.php';</script>";
                exit;
            }
        }
    }
}

// --- PROSES KONSUMSI PRIBADI (PRIVE BARANG) ---
if (isset($_POST['prive_barang']) || isset($_POST['simpan_prive'])) {
    
    $id_produk    = mysqli_real_escape_string($koneksi, $_POST['id_produk_prive'] ?? $_POST['id_produk']);
    $jumlah_prive = (int)($_POST['jumlah_prive'] ?? $_POST['jumlah']);
    $keterangan   = mysqli_real_escape_string($koneksi, $_POST['keterangan_prive'] ?? $_POST['keterangan']);
    
    // Stok awal (jika ada) untuk validasi agar tidak minus
    $stok_awal = isset($_POST['stok_awal_prive']) ? (int)$_POST['stok_awal_prive'] : 99999;
    $tanggal   = date('Y-m-d');
    
    if ($jumlah_prive > $stok_awal) {
        echo "<script>alert('Gagal: Jumlah yang diambil melebihi sisa stok!'); window.history.back();</script>";
        exit;
    } else {
        // Ambil data harga dari produk
        $cek_harga = mysqli_query($koneksi, "SELECT jenis_produk, modal, hpp FROM produk WHERE id_produk = '$id_produk'");
        $data_harga = mysqli_fetch_assoc($cek_harga);
        
        // Pilih harga berdasarkan jenisnya (agar nilai prive tidak Rp 0)
        $harga_satuan = ($data_harga['jenis_produk'] == 'Luar') ? $data_harga['modal'] : $data_harga['hpp'];
        
        // Hitung total nilai prive (kerugian)
        $total_hpp = $jumlah_prive * $harga_satuan;

        // Kurangi Stok
        $query_stok = "UPDATE produk SET stok = stok - $jumlah_prive WHERE id_produk = '$id_produk'";
        if (mysqli_query($koneksi, $query_stok)) {
            
            // Insert data prive dengan nilai $total_hpp yang sudah dihitung
            mysqli_query($koneksi, "INSERT INTO prive_barang (tanggal, id_produk, jumlah, total_hpp, keterangan) 
                                    VALUES ('$tanggal', '$id_produk', '$jumlah_prive', '$total_hpp', '$keterangan')");
                                    
            echo "<script>alert('Prive berhasil dicatat!'); window.location='stok.php';</script>";
            exit;
        }
    }
}

// --- PROSES HAPUS (SOFT DELETE) ---
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    mysqli_query($koneksi, "UPDATE produk  SET status_aktif  = 'N' WHERE id_produk = '$id'");
    echo "<script>alert('Data Berhasil Dihapus'); window.location='stok.php';</script>";
    exit;
}

// --- PROSES TAMBAH STOK CEPAT ---
if (isset($_POST['proses_tambah_cepat'])) {
    $id = mysqli_real_escape_string($koneksi, $_POST['id_produk_cepat']);
    $jumlah_tambah = (int)$_POST['jumlah_tambah'];
    $biaya_batch_baru = (int)str_replace('.', '', $_POST['biaya_batch_baru']); 

    $cek = mysqli_query($koneksi, "SELECT stok, jenis_produk, modal, hpp FROM produk WHERE id_produk = '$id'");
    $data = mysqli_fetch_assoc($cek);

    $stok_lama = (int)$data['stok'];
    $jenis = $data['jenis_produk'];
    $stok_baru_total = $stok_lama + $jumlah_tambah;

    if ($jenis == 'Produksi') {
        $hpp_lama = (int)$data['hpp'];
        $total_nilai_lama = $stok_lama * $hpp_lama;
        $hpp_baru = ceil(($total_nilai_lama + $biaya_batch_baru) / $stok_baru_total);
        $sql = "UPDATE produk SET stok = $stok_baru_total, hpp = $hpp_baru WHERE id_produk = '$id'";
    } else { 
        $modal_lama = (int)$data['modal'];
        $total_nilai_lama = $stok_lama * $modal_lama;
        $modal_baru = ceil(($total_nilai_lama + $biaya_batch_baru) / $stok_baru_total);
        $sql = "UPDATE produk SET stok = $stok_baru_total, modal = $modal_baru WHERE id_produk = '$id'";
    }
    
    if (mysqli_query($koneksi, $sql)) {
        echo "<script>alert('Stok dan Harga Modal/HPP berhasil diperbarui!'); window.location='stok.php';</script>";
        exit;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Stok Barang | 2 Paksi</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/stok.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet" />
    <style>
        /* ==================== FIX LAYOUT UTAMA ==================== */
        body {
            display: flex;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            background-color: #f8f9fa;
        }
        
        .menu-samping {
            width: 240px;
            flex-shrink: 0;
            position: fixed;
            height: 100vh;
        }

        .isi-halaman {
            flex-grow: 1;
            margin-left: 240px; 
            padding: 20px;
            max-width: calc(100% - 240px);
            box-sizing: border-box;
        }

        /* ==================== FIX HEADER & FILTER ==================== */
        .header-halaman {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
            width: 100%;
        }
        
        .baris-atas { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            width: 100%; 
        }

        .header-actions { 
            width: 100%; 
        }

        .filter-grup { 
            display: flex; 
            gap: 8px; 
            flex-wrap: wrap; 
            align-items: center; 
            width: 100%;
        }

        .filter-grup input[name="cari"] {
            flex: 1;
            min-width: 180px;
        }

        .filter-grup select, .filter-grup input { 
            padding: 8px 12px; 
            border: 1px solid #ccc; 
            border-radius: 6px; 
            font-family: inherit; 
            font-size: 14px;
        }

        .btn-filter { 
            padding: 8px 15px; 
            background: var(--cokelat-muda, #d4a373); 
            color: white; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 37px;
        }
        
        .btn-filter:hover { opacity: 0.9; }

        /* ==================== FIX TABEL RESPONSIVE ==================== */
        .tabel-wadah {
            width: 100%;
            overflow-x: auto; 
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap; 
        }

        table th, table td {
            padding: 12px 15px;
            text-align: left;
            font-size: 14px;
        }

       
        .aksi-lengkap-cell {
            display: flex; 
            gap: 10px; 
            justify-content: center; 
            align-items: center;
        }

        textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-family: inherit; resize: vertical; }
        .badge-rusak { background: #ffebee; color: #c62828; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-weight: bold; border: 1px solid #ef9a9a; transition: 0.2s;}
        .badge-rusak:hover { background: #e57373; color: white;}

        
        @media (max-width: 768px) {
            .menu-samping { display: none; }
            .isi-halaman { margin-left: 0; max-width: 100%; padding-top: 70px; }
            .baris-atas { flex-direction: row; }
        }
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

                    <select name="status_stok">
                        <option value="">Semua Status Stok</option>
                        <option value="habis" <?= (isset($_GET['status_stok']) && $_GET['status_stok'] == 'habis') ? 'selected' : '' ?>>Stok Habis</option>
                        <option value="kritis" <?= (isset($_GET['status_stok']) && $_GET['status_stok'] == 'kritis') ? 'selected' : '' ?>>Stok Kritis</option>
                        <option value="aman" <?= (isset($_GET['status_stok']) && $_GET['status_stok'] == 'aman') ? 'selected' : '' ?>>Stok Aman</option>
                    </select>

                    <select name="urutkan">
                        <option value="">Urutkan: Default (Stok)</option>
                        <option value="harga_tertinggi" <?= (isset($_GET['urutkan']) && $_GET['urutkan'] == 'harga_tertinggi') ? 'selected' : '' ?>>Harga Tertinggi</option>
                        <option value="harga_terendah" <?= (isset($_GET['urutkan']) && $_GET['urutkan'] == 'harga_terendah') ? 'selected' : '' ?>>Harga Terendah</option>
                        <option value="abjad_az" <?= (isset($_GET['urutkan']) && $_GET['urutkan'] == 'abjad_az') ? 'selected' : '' ?>>Abjad (A - Z)</option>
                        <option value="abjad_za" <?= (isset($_GET['urutkan']) && $_GET['urutkan'] == 'abjad_za') ? 'selected' : '' ?>>Abjad (Z - A)</option>
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
                        <th style="text-align: center;">Rusak (Klik Batal)</th>
                        <th style="text-align: center;">Aksi Lengkap</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $kondisi = ["status_aktif = 'Y'"];
                    if (!empty($_GET['cari'])) { $caribersih = trim($_GET['cari']); $kondisi[] = "nama_produk LIKE '%" . mysqli_real_escape_string($koneksi, $caribersih) . "%'"; }
                    if (!empty($_GET['jenis'])) { $kondisi[] = "jenis_produk = '" . mysqli_real_escape_string($koneksi, $_GET['jenis']) . "'"; }

                   $sql_where = count($kondisi) > 0 ? " WHERE " . implode(" AND ", $kondisi) : "";


                $sql_order = " ORDER BY stok ASC"; 

                if (!empty($_GET['urutkan'])) {
                    if ($_GET['urutkan'] == 'harga_tertinggi') {
                        $sql_order = " ORDER BY harga_satuan DESC";
                    } elseif ($_GET['urutkan'] == 'harga_terendah') {
                        $sql_order = " ORDER BY harga_satuan ASC";
                    } elseif ($_GET['urutkan'] == 'abjad_az') {
                        $sql_order = " ORDER BY nama_produk ASC";
                    } elseif ($_GET['urutkan'] == 'abjad_za') {
                        $sql_order = " ORDER BY nama_produk DESC";
                    }
                }


$sql = "SELECT * FROM produk" . $sql_where . $sql_order;
$res = mysqli_query($koneksi, $sql);
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
                        <td style="text-align: center;">
                            <?php if($rusak > 0): ?>
                                <span class="badge-rusak" title="Koreksi Salah Input Rusak" onclick="bukaModalKoreksi('<?= $row['id_produk'] ?>', '<?= addslashes($row['nama_produk']) ?>', '<?= $rusak ?>')">
                                    <i class="fa-solid fa-minus-circle"></i> <?= $rusak ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #ccc;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                             <div class="aksi-lengkap-cell">
                                 <i class="fa-solid fa-cart-plus" title="Tambah Stok Masuk" style="color: #27ae60; cursor:pointer; font-size: 1.1em;"
                                    onclick="bukaModalStokCepat('<?= $row['id_produk'] ?>', '<?= addslashes($row['nama_produk']) ?>')">
                                 </i>

                                 <i class="fa-solid fa-hand-holding-heart" title="Ambil Pribadi (Prive)" style="color: #3498db; cursor:pointer; font-size: 1.1em;"
                                    onclick="bukaModalPrive('<?= $row['id_produk'] ?>', '<?= addslashes($row['nama_produk']) ?>', '<?= $stok ?>')">
                                 </i>

                                 <i class="fa-solid fa-triangle-exclamation" title="Lapor Barang Rusak" style="color: #e74c3c; cursor:pointer; font-size: 1.1em;"
                                    onclick="bukaModalRusak('<?= $row['id_produk'] ?>', '<?= addslashes($row['nama_produk']) ?>', '<?= $stok ?>')">
                                 </i>
                                 
                                 <i class="fa-regular fa-pen-to-square aksi-edit" title="Edit Produk" style="color: var(--cokelat-tua); cursor:pointer;"
                                    onclick="bukaModal('edit', '<?= $row['id_produk'] ?>', '<?= addslashes($row['nama_produk']) ?>', '<?= $row['stok'] ?>', '<?= $row['harga_satuan'] ?>', '<?= $row['batas_minimal'] ?>', '<?= $jenis_p ?>', '<?= $row['modal']??0 ?>', '<?= $row['biaya_produksi']??0 ?>')">
                                 </i>
                                 
                                 <a href="stok.php?hapus=<?= $row['id_produk'] ?>" onclick="return confirm('Hapus produk <?= addslashes($row['nama_produk']) ?>?')" class="tombol-hapus-teks">Hapus</a>
                             </div>
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
                        <label>Total Biaya Production (Rp)</label>
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

    <div class="modal-bg" id="modalKoreksi">
        <div class="modal-konten">
            <div class="modal-head">
                <h3 style="color: #f39c12;"><i class="fa-solid fa-pen-to-square"></i> Kelola Barang Rusak</h3>
                <i class="fa-solid fa-xmark" style="cursor: pointer" onclick="tutupModal('modalKoreksi')"></i>
            </div>
            <form action="stok.php" method="POST" class="modal-body">
                <input type="hidden" name="id_produk_koreksi" id="kId">
                <p style="margin-bottom: 15px; font-size: 0.9em; color: #555;">
                    Produk: <strong id="kNamaProduk" style="color: #000;"></strong><br>
                    Tercatat Rusak: <strong id="kStokRusakTeks" style="color: red;"></strong>
                </p>
                
                <div class="input-grup">
                    <label>Jumlah barang yang akan diproses:</label>
                    <input type="number" name="jumlah_koreksi" id="kJumlah" min="1" required>
                </div>

                <div class="input-grup">
                    <label>Tindakan yang dilakukan:</label>
                    <select name="jenis_tindakan" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                        <option value="buang">Dibuang / Sudah Laku Obral (Hapus Permanen)</option>
                        <option value="kembalikan">Kembalikan ke Stok Normal (Salah Input)</option>
                    </select>
                    <small style="color: #888; display: block; margin-top: 5px;">*Jika pilih 'Hapus Permanen', angka rusak berkurang tapi stok tidak bertambah.</small>
                </div>
                
                <button type="submit" name="koreksi_rusak" class="btn-simpan" style="background: #f39c12;">Proses Barang</button>
            </form>
        </div>
    </div>
    
    <div class="modal-bg" id="modalPrive">
        <div class="modal-konten">
            <div class="modal-head"><h3 style="color:#3498db;">Ambil Pribadi (Prive)</h3><i class="fa-solid fa-xmark" style="cursor: pointer" onclick="tutupModal('modalPrive')"></i></div>
            <form action="stok.php" method="POST" class="modal-body">
                <input type="hidden" name="id_produk_prive" id="pId">
                <input type="hidden" name="stok_awal_prive" id="pStokAwal">
                <div class="input-grup"><label>Berapa yang diambil?</label><input type="number" name="jumlah_prive" id="pJumlah" min="1" required></div>
                <div class="input-grup"><label>Siapa yang ambil?</label><textarea name="keterangan_prive" required></textarea></div>
                <button type="submit" name="prive_barang" class="btn-simpan" style="background: #3498db;">Catat Prive</button>
            </form>
        </div>
    </div>

    <div class="modal-bg" id="modalStokCepat">
        <div class="modal-konten">
            <div class="modal-head"><h3>Tambah Stok</h3><i class="fa-solid fa-xmark" style="cursor: pointer" onclick="tutupModal('modalStokCepat')"></i></div>
            <form action="stok.php" method="POST" class="modal-body">
                <input type="hidden" name="id_produk_cepat" id="cId">
                <div class="input-grup"><label>Jumlah Tambah</label><input type="number" name="jumlah_tambah" required min="1"></div>
                <div class="input-grup"><label>Total Biaya (Rp)</label><input type="number" name="biaya_batch_baru" required min="0"></div>
                <button type="submit" name="proses_tambah_cepat" class="btn-simpan" style="background: #27ae60;">Update Stok</button>
            </form>
        </div>
    </div>

    <script>
        const modalUtama = document.getElementById('modalProduk');
        const modalRusak = document.getElementById('modalRusak');
        const modalKoreksi = document.getElementById('modalKoreksi');
        const modalStokCepat = document.getElementById('modalStokCepat');
        const modalPrive = document.getElementById('modalPrive');
        
        function ubahTampilanForm() {
            const jenis = document.getElementById('mJenis').value;
            document.getElementById('formLuar').style.display = jenis === 'Luar' ? 'block' : 'none';
            document.getElementById('formProduksi').style.display = jenis === 'Produksi' ? 'block' : 'none';
        }

        function bukaModal(mode, id = '', nama = '', stok = '', harga = '', batas = '10', jenis = 'Luar', modalVal = '0', biayaVal = '0') {
            modalUtama.style.display = 'flex';
            document.getElementById('mId').value = id; document.getElementById('mNama').value = nama;
            document.getElementById('mStok').value = stok; document.getElementById('mBatas').value = batas; 
            document.getElementById('mHarga').value = harga; document.getElementById('mJenis').value = jenis;
            document.getElementById('mModal').value = modalVal; document.getElementById('mBiaya').value = biayaVal;
            ubahTampilanForm(); 
            document.getElementById('mTitle').innerText = mode === 'edit' ? 'Edit Produk' : 'Tambah Produk Baru';
        }

        function bukaModalPrive(id, nama, stokSekarang) {
            modalPrive.style.display = 'flex';
            document.getElementById('pId').value = id; document.getElementById('pStokAwal').value = stokSekarang;
            document.getElementById('pJumlah').max = stokSekarang; 
        }

        function bukaModalRusak(id, nama, stokSekarang) {
            modalRusak.style.display = 'flex';
            document.getElementById('rId').value = id; document.getElementById('rStokAwal').value = stokSekarang;
            document.getElementById('rNamaProduk').innerText = nama; document.getElementById('rStokTeks').innerText = stokSekarang;
            document.getElementById('rJumlah').max = stokSekarang; 
        }

        function bukaModalKoreksi(id, nama, jumlahRusak) {
            modalKoreksi.style.display = 'flex';
            document.getElementById('kId').value = id; 
            document.getElementById('kNamaProduk').innerText = nama; 
            document.getElementById('kStokRusakTeks').innerText = jumlahRusak;
            document.getElementById('kJumlah').max = jumlahRusak; 
        }

        function bukaModalStokCepat(id, nama) {
            modalStokCepat.style.display = 'flex';
            document.getElementById('cId').value = id;
        }

        function tutupModal(idModal) { document.getElementById(idModal).style.display = 'none'; }
        
        window.onclick = function(event) { 
            if (event.target == modalUtama) tutupModal('modalProduk'); 
            if (event.target == modalRusak) tutupModal('modalRusak'); 
            if (event.target == modalPrive) tutupModal('modalPrive'); 
            if (event.target == modalStokCepat) tutupModal('modalStokCepat');
            if (event.target == modalKoreksi) tutupModal('modalKoreksi');
        }
    </script>
</body>
</html>