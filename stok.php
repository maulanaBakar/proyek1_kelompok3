<?php
include 'koneksi.php';
session_start();

if ($_SESSION['status'] != "login") {
    header("location:login.php?pesan=belum_login");
    exit();
}
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
                <a href="dashboard.php" class="link-menu">
                    <i class="fa-solid fa-house"></i> Beranda
                </a>
                <a href="kasir.php" class="link-menu">
                    <i class="fa-solid fa-cash-register"></i> Kasir
                </a>
                <a href="stok.php" class="link-menu aktif">
                    <i class="fa-solid fa-box"></i> Stok Barang
                </a>
                <a href="laporan.php" class="link-menu">
                    <i class="fa-solid fa-file-lines"></i> Laporan
                </a>
            </nav>
        </div>

        <div class="bagian-bawah">
            <a href="logout.php" class="link-menu keluar">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar
            </a>
        </div>
    </aside>

    <main class="isi-halaman">
        <header class="header-halaman">
            <h1>Manajemen Stok</h1>
            <button class="btn-tambah" onclick="bukaModal('tambah')">
                <i class="fa-solid fa-plus"></i> Tambah Produk
            </button>
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
                    $res = mysqli_query($koneksi, "SELECT * FROM produk");
                    while ($row = mysqli_fetch_assoc($res)):
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['nama_produk']) ?></strong></td>
                            <td><span style="color: var(--teks-abu)"><?= htmlspecialchars($row['kategori'] ?? 'Krupuk') ?></span></td>
                            <td>Rp <?= number_format($row['harga_satuan'], 0, ',', '.') ?></td>
                            <td><strong><?= $row['stok'] ?></strong> <small>Unit</small></td>
                            <td style="text-align: center; display: flex; gap: 10px; justify-content: center;">
                                <i class="fa-regular fa-pen-to-square aksi-edit" style="color: var(--cokelat-tua);"
                                   onclick="bukaModal('edit', '<?= $row['id_produk'] ?>', '<?= addslashes($row['nama_produk']) ?>', '<?= $row['stok'] ?>', '<?= addslashes($row['kategori']) ?>', '<?= $row['harga_satuan'] ?>')">
                                </i>

                                <a href="proses_stok.php?hapus=<?= $row['id_produk'] ?>"
                                   onclick="return confirm('Yakin ingin menghapus produk <?= addslashes($row['nama_produk']) ?>?')"
                                   class="tombol-hapus-teks">
                                   Hapus
                                </a>
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
            <form action="proses_stok.php" method="POST" class="modal-body">
                <input type="hidden" name="id_produk" id="mId">

                <div class="input-grup">
                    <label>Nama Produk</label>
                    <input type="text" name="nama_produk" id="mNama" placeholder="Masukkan nama krupuk..." required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px">
                    <div class="input-grup">
                        <label>Stok</label>
                        <input type="number" name="stok" id="mStok" required>
                    </div>
                    <div class="input-grup">
                        <label>Kategori</label>
                        <input type="text" name="kategori" id="mKat" placeholder="Contoh: Ikan">
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

    <script>
        const modal = document.getElementById('modalProduk');

        function bukaModal(mode, id = '', nama = '', stok = '', kat = '', harga = '') {
            modal.style.display = 'flex';
            document.getElementById('mId').value = id;
            document.getElementById('mNama').value = nama;
            document.getElementById('mStok').value = stok;
            document.getElementById('mKat').value = kat;
            document.getElementById('mHarga').value = harga;

            if (mode === 'edit') {
                document.getElementById('mTitle').innerText = 'Edit Produk';
                document.getElementById('mBtn').innerText = 'Simpan Perubahan';
            } else {
                document.getElementById('mTitle').innerText = 'Tambah Produk Baru';
                document.getElementById('mBtn').innerText = 'Simpan Produk';
            }
        }

        function tutupModal() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                tutupModal();
            }
        }
    </script>
</body>
</html>