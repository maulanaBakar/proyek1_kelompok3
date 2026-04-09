<?php
include 'koneksi.php';
session_start();

if($_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login");
    exit();
}
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>2 Paksi | Management Inventori</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
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
          <a href="stok.php" class="nav-link active"><i class="fa-solid fa-box"></i> Stok Barang</a>
          <a href="laporan.php" class="nav-link"><i class="fa-solid fa-file-invoice-dollar"></i> Laporan</a>
        </div>
      </div>
      <div class="logout-section">
        <a href="logout.php" class="nav-link logout-link"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
      </div>
    </div>

    <div class="content">
      <header class="header">
        <h1>Management Inventori</h1>
        <button class="btn-add" onclick="toggleModal('tambah')">
          <i class="fa-solid fa-plus"></i> Tambah Produk
        </button>
      </header>

      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Nama produk</th>
              <th>Kategori</th>
              <th>Harga jual</th>
              <th>Stok</th>
              <th style="text-align: center;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $res = mysqli_query($koneksi, "SELECT * FROM produk");
            while($row = mysqli_fetch_assoc($res)): ?>
            <tr>
              <td><strong style="font-weight: 700;"><?= $row['nama_produk'] ?></strong></td>
              <td><span style="color: var(--text-muted); font-weight: 500;"><?= $row['kategori'] ?? 'Krupuk' ?></span></td>
              <td><span class="price-tag">Rp <?= number_format($row['harga_satuan'], 0, ',', '.') ?></span></td>
              <td>
                <span style="font-weight: 700; color: var(--sidebar-bg);"><?= $row['stok'] ?></span> 
                <span style="font-size: 11px; color: var(--text-muted);">Unit</span>
              </td>
              <td style="text-align: center;">
                <div class="btn-action" onclick="toggleModal('edit', '<?= $row['id_produk'] ?>', '<?= $row['nama_produk'] ?>', '<?= $row['stok'] ?>', '<?= $row['kategori'] ?>', '<?= $row['harga_satuan'] ?>')">
                  <i class="fa-regular fa-pen-to-square"></i>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="modal-bg" id="modalProduk">
      <div class="modal-content">
        <div class="modal-header">
          <h3 id="mTitle">Edit produk</h3>
          <i class="fa-solid fa-xmark" style="cursor: pointer; font-size: 18px;" onclick="toggleModal()"></i>
        </div>
        <div class="modal-body">
          <form action="proses_stok.php" method="POST">
            <input type="hidden" name="id_produk" id="mId" />
            <div class="input-box full">
              <label>Nama Produk</label>
              <input type="text" name="nama_produk" id="mNama" placeholder="Masukkan nama krupuk..." required />
            </div>
            <div class="form-grid">
              <div class="input-box">
                <label>Stok</label>
                <input type="number" name="stok" id="mStok" required />
              </div>
              <div class="input-box">
                <label>Kategori</label>
                <input type="text" name="kategori" id="mKat" placeholder="Contoh: Krupuk Ikan" />
              </div>
            </div>
            <div class="input-box full">
              <label>Harga Jual (Rp)</label>
              <input type="number" name="harga_jual" id="mHarga" required />
            </div>
            <div class="modal-footer">
              <button type="button" class="btn-cancel" onclick="toggleModal()">Batal</button>
              <button type="submit" name="save" id="mBtn" class="btn-save">Simpan Perubahan</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script src="script.js"></script>
  </body>
</html>