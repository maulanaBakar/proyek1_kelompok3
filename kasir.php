<?php
include 'koneksi.php';
session_start();

// 1. CEK LOGIN
if($_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login");
    exit();
}

// 2. INISIALISASI KERANJANG
if(!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// 3. LOGIKA TAMBAH PRODUK
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
                'diskon' => $p['diskon'] 
            ];
        }
    }
    header("location:kasir.php"); 
    exit();
}

// 4. LOGIKA UPDATE QTY
if(isset($_POST['update_qty'])) {
    $id = $_POST['id_produk'];
    $qty = (int)$_POST['qty'];
    if($qty < 1) $qty = 1;

    $data = mysqli_query($koneksi, "SELECT stok FROM produk WHERE id_produk='$id'");
    $p = mysqli_fetch_assoc($data);

    if($qty > $p['stok']) {
        echo "<script>alert('Qty melebihi stok! Maksimal: ".$p['stok']."'); window.location='kasir.php';</script>";
        exit();
    } else {
        $_SESSION['keranjang'][$id]['qty'] = $qty;
        header("location:kasir.php");
        exit();
    }
}

// 5. LOGIKA HAPUS ITEM
if(isset($_GET['aksi']) && $_GET['aksi'] == "hapus") {
    $id = $_GET['id_produk'];
    unset($_SESSION['keranjang'][$id]);
    header("location:kasir.php");
    exit();
}

// 6. LOGIKA PROSES BAYAR
if(isset($_POST['proses_bayar'])) {
    if(!empty($_SESSION['keranjang'])) {
        $total_bayar = $_POST['total_bayar'];
        $tgl = date("Y-m-d H:i:s");

        mysqli_query($koneksi, "INSERT INTO transaksi (tanggal_transaksi, total_pendapatan) VALUES ('$tgl', '$total_bayar')");
        $id_transaksi = mysqli_insert_id($koneksi);

        foreach($_SESSION['keranjang'] as $id_produk => $item) {
            $qty = $item['qty'];
            $diskon = $item['diskon'] ?? 0;
            $harga_final = $item['harga'] - ($item['harga'] * $diskon / 100);
            $subtotal = $harga_final * $qty;

            mysqli_query($koneksi, "UPDATE produk SET stok = stok - $qty WHERE id_produk = '$id_produk'");
            mysqli_query($koneksi, "INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah_produk, subtotal) 
                                   VALUES ('$id_transaksi', '$id_produk', '$qty', '$subtotal')");
        }

        unset($_SESSION['keranjang']);
        echo "<script>alert('Pembayaran berhasil'); window.location='kasir.php';</script>";
    }
}

// 7. LOGIKA PENCARIAN & PENGURUTAN
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$urut = isset($_GET['urut']) ? $_GET['urut'] : 'default';

$sql = "SELECT * FROM produk WHERE stok > 0";
if ($cari != "") {
    $sql .= " AND (nama_produk LIKE '%$cari%' OR kategori LIKE '%$cari%')";
}

if ($urut == "stok_kecil") {
    $sql .= " ORDER BY CAST(stok AS UNSIGNED) ASC";
} elseif ($urut == "stok_besar") {
    $sql .= " ORDER BY CAST(stok AS UNSIGNED) DESC";
} else {
    $sql .= " ORDER BY id_produk DESC";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>2 Paksi | Kasir Penjualan</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
    <style>
:root {
    --primary: #4a3e3d;
    --accent: #d6c4b0;
    --bg-body: #fdfbf7;
    --bg-card: #ffffff;
    --text-main: #2d2424;
    --text-muted: #888888;
    --green: #27ae60;
    --orange: #e67e22;
    --red: #e74c3c;
    --gray-disabled: #dcdde1;
    --border-light: #f1f2f6;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Plus Jakarta Sans", sans-serif;
}

body {
    background-color: var(--bg-body);
    color: var(--text-main);
    line-height: 1.5;
    font-size: 14px;
}

.content {
    margin-left: 260px;
    padding: 30px 40px;
}

.header h1 {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 25px;
}

.main-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 30px;
    align-items: start;
}

.filter-wrapper {
    display: flex;
    gap: 12px;
    margin-bottom: 30px;
    align-items: center;
}

.search-input {
    flex: 1;
    padding: 12px 18px;
    border: 1px solid #ddd;
    border-radius: 10px;
    background: var(--bg-card);
    font-size: 14px;
    color: var(--text-main);
}

.select-urut {
    padding: 12px 15px;
    border-radius: 10px;
    border: 1px solid #ddd;
    background: var(--bg-card);
    cursor: pointer;
    color: var(--text-main);
}

.btn-cari {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 12px 25px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
}

.produk-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 20px;
}

.card-produk {
    background: var(--bg-card);
    padding: 25px 15px;
    border-radius: 15px;
    text-align: center;
    text-decoration: none;
    color: inherit;
    border: 1px solid var(--border-light);
    transition: all 0.3s ease;
}

.card-produk:hover {
    border-color: var(--accent);
    box-shadow: 0 10px 20px rgba(0,0,0,0.02);
}

.card-produk h4 {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-main);
    margin-bottom: 8px;
    text-transform: capitalize;
}

.card-produk .harga {
    font-size: 17px;
    font-weight: 700;
    color: var(--primary);
}

.card-produk .stok-info {
    margin-top: 12px;
    padding: 4px 12px;
    background: #fff5eb;
    color: var(--orange);
    border-radius: 50px;
    font-size: 11px;
    font-weight: 600;
}

.cart-panel {
    background: var(--bg-card);
    border-radius: 20px;
    overflow: hidden;
    position: sticky;
    top: 30px;
    border: 1px solid var(--border-light);
}

.cart-header {
    background: var(--primary);
    color: #fff;
    padding: 18px 20px;
    text-align: center;
    font-weight: 600;
    font-size: 16px;
}

.cart-items {
    max-height: 400px;
    overflow-y: auto;
    padding: 10px 20px;
}

.item-row {
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
}

.item-name {
    font-weight: 600;
    font-size: 14px;
}

.item-subtotal {
    font-weight: 700;
    font-size: 15px;
    color: var(--text-main);
}

.qty-control {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    background: #f8f9fa;
    border-radius: 50px;
    padding: 4px;
    width: fit-content;
    border: 1px solid #eee;
}

.qty-control button {
    width: 30px;
    height: 30px;
    border: none;
    background: transparent;
    color: var(--primary);
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    border-radius: 50%;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qty-control button:hover {
    background: #e2e6ea;
}

.qty-input {
    width: 35px;
    text-align: center;
    border: none;
    background: transparent;
    font-size: 14px;
    font-weight: 700;
    color: var(--text-main);
    padding: 0;
    margin: 0 5px;
}

.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.qty-input[type=number] {
    -moz-appearance: textfield;
}

.trash-btn {
    color: var(--red);
    font-size: 16px;
    text-decoration: none;
}

.cart-footer {
    padding: 20px 25px;
    background: #f8f9fa;
}

.total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 18px;
    font-weight: 700;
    font-size: 15px;
}

.total-amount {
    font-size: 22px;
    color: var(--primary);
}

.btn-bayar {
    width: 100%;
    padding: 15px;
    border-radius: 10px;
    border: 1px solid #2d2424;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s ease;
    background: var(--bg-card);
    color: var(--text-main);
    cursor: pointer;
}

.btn-bayar:disabled {
    background: #e9ecef;
    border-color: #ddd;
    color: #a1a1a1;
    cursor: not-allowed;
}

.btn-bayar:not(:disabled) {
    background-color: var(--green);
    color: #ffffff;
    border-color: var(--green);
}

.btn-bayar:not(:disabled):hover {
    background-color: #219150;
    transform: translateY(-2px);
}
    </style>
</head>
<body>

    <aside class="menu-samping">
      <div class="bagian-atas">
        <div class="judul-logo">2 PAKSI</div>
        <nav class="daftar-menu">
          <a href="dashboard.php" class="link-menu"><i class="fa-solid fa-house"></i> Beranda</a>
          <a href="kasir.php" class="link-menu aktif"><i class="fa-solid fa-cash-register"></i> Kasir</a>
          <a href="stok.php" class="link-menu"><i class="fa-solid fa-box"></i> Stok Barang</a>
          <a href="laporan.php" class="link-menu"><i class="fa-solid fa-file-lines"></i> Laporan</a>
        </nav>
      </div>
      <div class="bagian-bawah">
        <a href="logout.php" class="link-menu keluar"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a>
      </div>
    </aside>

    <div class="content">
        <header class="header"><h1>Kasir Penjualan</h1></header>
        
        <div class="main-grid">
            <div class="produk-section">
                <form action="" method="GET" class="filter-wrapper">
                    <input type="text" name="cari" class="search-input" placeholder="Cari produk..." value="<?= htmlspecialchars($cari) ?>">
                    
                    <select name="urut" class="select-urut" onchange="this.form.submit()">
                        <option value="default">Urutkan Produk</option>
                        <option value="stok_kecil" <?= ($urut == 'stok_kecil') ? 'selected' : '' ?>>Stok: Sedikit → Banyak</option>
                        <option value="stok_besar" <?= ($urut == 'stok_besar') ? 'selected' : '' ?>>Stok: Banyak → Sedikit</option>
                    </select>

                    <button type="submit" class="btn-cari">Cari</button>
                    <?php if($cari != "" || $urut != "default"): ?>
                        <a href="kasir.php" style="align-self:center; color:#999; text-decoration:none; font-size:13px;">Reset</a>
                    <?php endif; ?>
                </form>

                <div class="produk-grid">
                    <?php 
                    $res = mysqli_query($koneksi, $sql);
                    if(mysqli_num_rows($res) > 0):
                        while($p = mysqli_fetch_assoc($res)): 
                    ?>
                    <a href="?aksi=tambah&id_produk=<?= $p['id_produk'] ?>" class="card-produk">
                        <h4><?= htmlspecialchars($p['nama_produk']) ?></h4>
                        <span class="harga">Rp <?= number_format($p['harga_satuan'], 0, ',', '.') ?></span>
                        <div class="stok-info">Stok: <?= $p['stok'] ?></div>
                    </a>
                    <?php endwhile; else: ?>
                        <div style="grid-column:1/-1; text-align:center; padding:100px; color:#ccc;">
                            <i class="fa-solid fa-box-open fa-3x"></i>
                            <p style="margin-top:15px;">Produk tidak ditemukan.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="cart-panel">
                <div class="cart-header">Item Terpilih</div>
                <div class="cart-items">
                    <?php 
                    $total = 0;
                    if(empty($_SESSION['keranjang'])): ?>
                        <div style="text-align:center; padding: 60px 0; color: #ccc;">Keranjang Kosong</div>
                    <?php else: 
                        foreach($_SESSION['keranjang'] as $id => $item): 
                            $diskon = $item['diskon'] ?? 0;
                            $harga_final = $item['harga'] - ($item['harga'] * $diskon / 100);
                            $sub = $harga_final * $item['qty'];
                            $total += $sub;
                    ?>
                        <div class="item-row">
                            <div class="item-top" style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                <span class="item-name"><?= htmlspecialchars($item['nama']) ?></span>
                                <span class="item-subtotal">Rp <?= number_format($sub, 0, ',', '.') ?></span>
                            </div>
                            
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <form method="POST" class="qty-control">
                                    <input type="hidden" name="id_produk" value="<?= $id ?>">
                                    <button type="button" onclick="let q = this.nextElementSibling; if(parseInt(q.value) > 1){ q.stepDown(); this.form.submit(); } else { if(confirm('Hapus item?')){ window.location.href='?aksi=hapus&id_produk=<?= $id ?>'; } }">-</button>
                                    
                                    <input type="number" name="qty" class="qty-input" value="<?= $item['qty'] ?>" min="1" onchange="this.form.submit()">
                                    
                                    <button type="button" onclick="this.previousElementSibling.stepUp(); this.form.submit();">+</button>
                                    <input type="hidden" name="update_qty" value="1">
                                </form>
                                <a href="?aksi=hapus&id_produk=<?= $id ?>" class="trash-btn" onclick="return confirm('Hapus item?')"><i class="fa-solid fa-trash-can"></i></a>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <div class="cart-footer">
                    <div class="total-row">
                        <span>Total</span>
                        <span style="color: var(--green);">Rp <?= number_format($total, 0, ',', '.') ?></span>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="total_bayar" value="<?= $total ?>">
                        <button type="submit" name="proses_bayar" class="btn-bayar" <?= ($total == 0) ? 'disabled' : '' ?>>
                            Selesaikan Pembayaran
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>