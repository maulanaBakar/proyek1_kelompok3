<?php
// 1. Panggil file koneksi database
require_once 'koneksi.php'; 
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
                <!-- <a href="pengaturan.php" class="link-menu"><i class="fa-solid fa-gear"></i> <span>Pengaturan</span></a> -->
            </nav>
        </div>
        <div class="bagian-bawah">
            <a href="logout.php" class="link-menu keluar"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a>
        </div>
    </aside>

    <main class="isi-halaman">
        <header class="header-halaman">
            <h1>Manajemen Stok</h1>
            <div class="header-actions">
                <form action="stok.php" method="GET" class="search-box">
                    <input type="text" name="cari" placeholder="Cari nama produk..." value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '' ?>">
                    <button type="submit" class="btn-cari"><i class="fa-solid fa-search"></i></button>
                </form>
                <button class="btn-tambah" type="button" onclick="bukaModal('tambah')">
                    <i class="fa-solid fa-plus"></i> Tambah Produk
                </button>
            </div>
        </header>

        <div class="tabel-wadah">
            <table>
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Kategori</th>
                        <th>Harga Jual</th>
                        <th>Stok</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
                    $sql = "SELECT * FROM produk ORDER BY stok ASC";
                    if ($cari != "") {
                        $sql .= " WHERE nama_produk LIKE '%$cari%' OR kategori LIKE '%$cari%'";
                    }
                    // $sql .= " ORDER BY stok ASC"; 

                    $res = mysqli_query($koneksi, $sql);
                    while ($row = mysqli_fetch_assoc($res)):
                        $stok = $row['stok'];
                        if ($stok <= 0) { $class = "stok-habis"; $label = "Habis"; }
                        elseif ($stok <= 10) { $class = "stok-kritis"; $label = "Hampir Habis"; }
                        else { $class = "stok-aman"; $label = "Aman"; }
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['nama_produk']) ?></strong></td>
                        <td><span style="color: var(--teks-abu)"><?= htmlspecialchars($row['kategori'] ?? 'Krupuk') ?></span></td>
                        <td style="line-height: 1.4;">
    <?php 
        $harga_asli = $row['harga_satuan'];
        $diskon = isset($row['diskon']) ? $row['diskon'] : 0;
        
        if ($diskon > 0) {
            $harga_final = $harga_asli - ($harga_asli * $diskon / 100);
            echo '<div style="font-weight: 700; color: var(--teks-gelap);">Rp ' . number_format($harga_final, 0, ',', '.') . '</div>';
            echo '<div style="font-size: 11px; color: #999;">';
            echo '<span style="text-decoration: line-through;">Rp ' . number_format($harga_asli, 0, ',', '.') . '</span>';
            echo ' <span class="badge-diskon">-' . $diskon . '%</span>';
            echo '</div>';
        } else {
            echo '<div style="font-weight: 700; color: var(--teks-gelap);">Rp ' . number_format($harga_asli, 0, ',', '.') . '</div>';
        }
    ?>
</td>
                        <td>
                            <span class="badge-stok <?= $class ?>">
                                <?= $stok ?> - <?= $label ?>
                            </span>
                        </td>
                        <td style="text-align: center; display: flex; gap: 10px; justify-content: center;">
                             <i class="fa-regular fa-pen-to-square aksi-edit" style="color: var(--cokelat-tua); cursor:pointer;"
                                onclick="bukaModal('edit', '<?= $row['id_produk'] ?>', '<?= addslashes($row['nama_produk']) ?>', '<?= $row['stok'] ?>', '<?= addslashes($row['kategori']) ?>', '<?= $row['harga_satuan'] ?>')">
                             </i>
                             <a href="proses_stok.php?hapus=<?= $row['id_produk'] ?>"
                                onclick="return confirm('Yakin ingin menghapus produk <?= addslashes($row['nama_produk']) ?>?')"
                                class="tombol-hapus-teks">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal-bg" id="modalProduk">
        <div class="modal-konten">
            <div class="modal-head">
                <h3 id="mTitle">Tambah Produk</h3>
                <i class="fa-solid fa-xmark" style="cursor: pointer" onclick="tutupModal()"></i>
            </div>
            <form action="proses_stok.php" method="POST" class="modal-body" enctype="multipart/form-data">
                <input type="hidden" name="id_produk" id="mId">
                <div class="input-grup">
                    <label>Nama Produk</label>
                    <input type="text" name="nama_produk" id="mNama" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px">
                    <div class="input-grup">
                        <label>Stok</label>
                        <input type="number" name="stok" id="mStok" required>
                    </div>
                    <div class="input-grup">
                        <label>Kategori</label>
                        <input type="text" name="kategori" id="mKat">
                    </div>
                </div>
                <div class="input-grup">
                    <label>Harga Jual (Rp)</label>
                    <input type="number" name="harga_jual" id="mHarga" required>
                </div>
                <div class="input-grup">
                   <label>Diskon (%)</label>
                    <input type="number" name="diskon" id="mDiskon" placeholder="0" min="0" max="100">
                </div>
                <div class="input-grup">
                    <label>Foto Produk</label>
                    <input type="file" name="gambar_produk" accept="image/*">
                </div>
                <button type="submit" name="save" class="btn-simpan" id="mBtn">Simpan Data</button>
                
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modalProduk');
        // Update fungsi bukaModal
function bukaModal(mode, id = '', nama = '', stok = '', kat = '', harga = '', diskon = '0') {
    modal.style.display = 'flex';
    document.getElementById('mId').value = id;
    document.getElementById('mNama').value = nama;
    document.getElementById('mStok').value = stok;
    document.getElementById('mKat').value = kat;
    document.getElementById('mHarga').value = harga;
    document.getElementById('mDiskon').value = diskon; // Tambahkan ini

    if (mode === 'edit') {
        document.getElementById('mTitle').innerText = 'Edit Produk';
        document.getElementById('mBtn').innerText = 'Simpan Perubahan';
    } else {
        document.getElementById('mTitle').innerText = 'Tambah Produk Baru';
        document.getElementById('mBtn').innerText = 'Simpan Produk';
    }
}
        function tutupModal() { modal.style.display = 'none'; }
        window.onclick = function(event) { if (event.target == modal) tutupModal(); }
    </script>
</body>
</html>