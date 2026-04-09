<?php
include 'koneksi.php';
session_start();

// 1. Cek Login
if($_SESSION['status'] != "login"){
    header("location:login.php?pesan=belum_login");
    exit();
}

// 2. Inisialisasi Keranjang
if(!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// --- 3. LOGIKA SISTEM (TAMBAH, KURANG, HAPUS, BAYAR) ---

// A. Tambah atau Tambah Kuantitas (+)
if(isset($_GET['aksi']) && $_GET['aksi'] == "tambah") {
    $id = $_GET['id_produk'];
    $data = mysqli_query($koneksi, "SELECT * FROM produk WHERE id_produk='$id'");
    $p = mysqli_fetch_assoc($data);

    if($p) {
        if(isset($_SESSION['keranjang'][$id])) {
            $_SESSION['keranjang'][$id]['qty'] += 1;
        } else {
            $_SESSION['keranjang'][$id] = [
                'nama'  => $p['nama_produk'],
                'harga' => $p['harga_satuan'],
                'qty'   => 1
            ];
        }
    }
    header("location:kasir.php");
    exit();
}

// B. Kurangi Kuantitas (-)
if(isset($_GET['aksi']) && $_GET['aksi'] == "kurang") {
    $id = $_GET['id_produk'];
    if(isset($_SESSION['keranjang'][$id])) {
        $_SESSION['keranjang'][$id]['qty'] -= 1;
        if($_SESSION['keranjang'][$id]['qty'] <= 0) {
            unset($_SESSION['keranjang'][$id]);
        }
    }
    header("location:kasir.php");
    exit();
}

// C. Hapus Item dari Keranjang
if(isset($_GET['aksi']) && $_GET['aksi'] == "hapus") {
    $id = $_GET['id_produk'];
    unset($_SESSION['keranjang'][$id]);
    header("location:kasir.php");
    exit();
}

// D. Proses Selesaikan Pembayaran
if(isset($_POST['proses_bayar'])) {
    if(!empty($_SESSION['keranjang'])) {
        $total_bayar = $_POST['total_bayar'];
        $id_admin = $_SESSION['id_admin'] ?? 1;
        $tgl = date('Y-m-d H:i:s');

        // Simpan ke tabel transaksi
        $query_t = "INSERT INTO transaksi (id_admin, tanggal_transaksi, total_pendapatan) 
                    VALUES ('$id_admin', '$tgl', '$total_bayar')";
        mysqli_query($koneksi, $query_t);
        $id_transaksi = mysqli_insert_id($koneksi);

        // Simpan ke detail_transaksi & Potong Stok
        foreach($_SESSION['keranjang'] as $id_p => $item) {
            $qty = $item['qty'];
            $subtotal = $item['harga'] * $qty;
            
            // Menggunakan nama kolom sesuai database: jumlah_produk & subtotal
            $query_d = "INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah_produk, subtotal) 
                        VALUES ('$id_transaksi', '$id_p', '$qty', '$subtotal')";
            mysqli_query($koneksi, $query_d);

            // Update stok di tabel produk
            mysqli_query($koneksi, "UPDATE produk SET stok = stok - $qty WHERE id_produk = '$id_p'");
        }

        unset($_SESSION['keranjang']);
        echo "<script>
            alert('PEMBAYARAN BERHASIL!\\nTotal: Rp " . number_format($total_bayar, 0, ',', '.') . "');
            window.location='kasir.php';
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>2 Paksi | Kasir Penjualan</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #4A3E3D; /* Cokelat Tua Hangat & Profesional */
            --sidebar-hover: rgba(255, 255, 255, 0.08);
            --accent-color: #D6C4B0; /* Cokelat Susu/Krem Halus */
            --accent-hover: #C4B19C; 
            --bg-body: #FDFBF7; /* Putih rona Krem */
            --text-dark: #3E3636; /* Warna teks cokelat gelap */
            --text-muted: #8C7A78; /* Warna teks sekunder */
            --white: #ffffff;
            --sidebar-width: 260px;
            --shadow-sm: 0 1px 3px rgba(74, 62, 61, 0.05);
            --shadow-md: 0 4px 12px rgba(74, 62, 61, 0.08);
        }
        
        * { margin:0; padding:0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-body); display: flex; min-height: 100vh; color: var(--text-dark); }
        
        /* --- MOBILE HEADER TOGGLE --- */
        .mobile-toggle {
            display: none;
            background-color: var(--white);
            padding: 15px 20px;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #F2EBE1;
        }
        .mobile-toggle .brand {
            font-weight: 800;
            color: var(--sidebar-bg);
            letter-spacing: 1px;
        }
        .mobile-toggle button {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--sidebar-bg);
            cursor: pointer;
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            padding: 30px 20px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            z-index: 1001;
            transition: all 0.3s ease;
        }

        .logo {
            font-size: 22px;
            font-weight: 800;
            text-align: center;
            letter-spacing: 1.5px;
            color: var(--accent-color);
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .nav-menu {
            list-style: none;
            margin-top: 25px;
            flex-grow: 1;
        }

        .nav-item {
            margin-bottom: 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            color: #D2C4C3;
            text-decoration: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .nav-link i {
            margin-right: 14px;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        .nav-link:hover {
            background: var(--sidebar-hover);
            color: var(--accent-color);
        }

        .nav-link.active {
            background: var(--accent-color);
            color: var(--sidebar-bg);
            box-shadow: 0 4px 12px rgba(214, 196, 176, 0.2);
        }

        .logout-section {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 20px;
        }

        .logout-link {
            color: #FCA5A5;
        }
        .logout-link:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }

        /* --- MAIN CONTENT --- */
        .content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            padding: 40px 50px;
            transition: all 0.3s ease;
        }

        .header h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.5px;
            margin-bottom: 30px;
        }
        
        .main-grid { display: grid; grid-template-columns: 1fr 380px; gap: 30px; }

        /* --- PRODUK LIST --- */
        .produk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 20px; align-content: start; }
        .card-produk { 
            background: var(--white); 
            padding: 15px; 
            border-radius: 20px; 
            text-align: center; 
            text-decoration: none; 
            color: inherit; 
            box-shadow: var(--shadow-sm); 
            transition: 0.3s; 
            border: 1px solid #F2EBE1; 
        }
        .card-produk:hover { transform: translateY(-5px); border-color: var(--accent-color); box-shadow: var(--shadow-md); }
        .card-produk img { width: 100%; height: 110px; object-fit: cover; border-radius: 15px; margin-bottom: 12px; background: #FDFBF7; border: 1px solid #F2EBE1; }

        /* --- KERANJANG PANEL --- */
        .cart-panel { background: var(--white); border-radius: 20px; box-shadow: var(--shadow-sm); height: fit-content; position: sticky; top: 40px; overflow: hidden; border: 1px solid #F2EBE1; }
        .cart-header { background: var(--sidebar-bg); color: var(--accent-color); padding: 20px; text-align: center; font-weight: 700; font-size: 16px; letter-spacing: 0.5px; }
        .cart-items { max-height: 400px; overflow-y: auto; padding: 10px 20px; }
        .item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #F9F6F0; }
        .item:last-child { border-bottom: none; }
        
        .qty-btns { display: flex; align-items: center; gap: 10px; margin-top: 5px; }
        .btn-small { width: 28px; height: 28px; border-radius: 8px; border: 1px solid #F2EBE1; background: #FDFBF7; display: flex; align-items: center; justify-content: center; text-decoration: none; color: var(--text-dark); font-size: 11px; transition: 0.2s; }
        .btn-small:hover { background: var(--accent-color); color: var(--sidebar-bg); border-color: var(--accent-color); }

        .cart-footer { padding: 25px; background: #FDFBF7; border-top: 1px solid #F2EBE1; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 20px; font-weight: 800; color: var(--sidebar-bg); }
        .btn-bayar { width: 100%; background: #27ae60; color: var(--white); border: none; padding: 16px; border-radius: 15px; font-weight: 700; cursor: pointer; font-size: 15px; transition: 0.3s; box-shadow: 0 4px 12px rgba(39, 174, 96, 0.15); }
        .btn-bayar:hover { background: #219150; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(39, 174, 96, 0.25); }
        .btn-bayar:disabled { background: #E2E8F0; color: #94A3B8; cursor: not-allowed; transform: none; box-shadow: none; }

        /* --- RESPONSIVE MOBILE --- */
        @media (max-width: 992px) {
            body { flex-direction: column; }
            .mobile-toggle { display: flex; }
            .sidebar { width: 240px; transform: translateX(-240px); }
            .sidebar.active { transform: translateX(0); }
            .content { margin-left: 0; width: 100%; padding: 90px 20px 30px 20px; }
            .main-grid { grid-template-columns: 1fr; }
            .cart-panel { position: static; }
        }
    </style>
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
                <a href="kasir.php" class="nav-link active"><i class="fa-solid fa-cash-register"></i> Kasir</a>
                <a href="stok.php" class="nav-link"><i class="fa-solid fa-box"></i> Stok Barang</a>
                <a href="laporan.php" class="nav-link"><i class="fa-solid fa-file-invoice-dollar"></i> Laporan</a>
            </div>
        </div>
        <div class="logout-section">
            <a href="logout.php" class="nav-link logout-link"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
        </div>
    </div>

    <div class="content">
        <header class="header">
            <h1>Kasir Penjualan</h1>
        </header>
        
        <div class="main-grid">
            <div class="produk-grid">
                <?php 
                $res = mysqli_query($koneksi, "SELECT * FROM produk WHERE stok > 0");
                while($p = mysqli_fetch_assoc($res)): 
                ?>
                <a href="?aksi=tambah&id_produk=<?= $p['id_produk'] ?>" class="card-produk">
                    <img src="assets/img/<?= $p['gambar_produk'] ?>" onerror="this.src='https://via.placeholder.com/150?text=Produk'">
                    <h4 style="font-size: 13px; margin-bottom: 4px; font-weight: 700; color: var(--text-dark);"><?= $p['nama_produk'] ?></h4>
                    <p style="color: var(--text-muted); font-weight: 700; font-size: 12px; margin-bottom: 2px;">Rp <?= number_format($p['harga_satuan'], 0, ',', '.') ?></p>
                    <small style="color: #A09391; font-size: 11px; font-weight: 500;">Stok: <?= $p['stok'] ?></small>
                </a>
                <?php endwhile; ?>
            </div>

            <div class="cart-panel">
                <div class="cart-header">Item Terpilih</div>
                <div class="cart-items">
                    <?php 
                    $total = 0;
                    if(empty($_SESSION['keranjang'])): ?>
                        <div style="text-align:center; padding: 50px 0; color: #D2C4C3;">
                            <i class="fa-solid fa-basket-shopping" style="font-size: 36px; margin-bottom: 12px;"></i>
                            <p style="font-size: 13px; font-weight: 500;">Belum ada produk</p>
                        </div>
                    <?php else: 
                        foreach($_SESSION['keranjang'] as $id => $item): 
                        $sub = $item['harga'] * $item['qty'];
                        $total += $sub;
                    ?>
                        <div class="item">
                            <div>
                                <b style="font-size: 13px; font-weight: 700; color: var(--text-dark);"><?= $item['nama'] ?></b>
                                <div class="qty-btns">
                                    <a href="?aksi=kurang&id_produk=<?= $id ?>" class="btn-small"><i class="fa-solid fa-minus"></i></a>
                                    <span style="font-weight: 700; min-width: 20px; text-align: center; font-size: 13px;"><?= $item['qty'] ?></span>
                                    <a href="?aksi=tambah&id_produk=<?= $id ?>" class="btn-small"><i class="fa-solid fa-plus"></i></a>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <b style="color: var(--sidebar-bg); font-size: 13px;">Rp <?= number_format($sub, 0, ',', '.') ?></b><br>
                                <a href="?aksi=hapus&id_produk=<?= $id ?>" style="color: #ef4444; text-decoration: none; font-size: 11px; font-weight: 600;"><i class="fa-solid fa-trash" style="font-size: 10px;"></i> Hapus</a>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <div class="cart-footer">
                    <div class="total-row">
                        <span style="font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);">Total</span>
                        <span style="font-size: 22px; font-weight: 800; color: var(--sidebar-bg);">Rp <?= number_format($total, 0, ',', '.') ?></span>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="total_bayar" value="<?= $total ?>">
                        <button type="submit" name="proses_bayar" class="btn-bayar" <?= ($total == 0) ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-check-double" style="margin-right: 6px;"></i> Selesaikan Pembayaran
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // SCRIPT MENU TOGGLE UNTUK HP
        const menuBtn = document.getElementById('menu-btn');
        const sidebar = document.getElementById('sidebar');
        
        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Tutup menu saat mengklik di luar sidebar di HP
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !menuBtn.contains(e.target) && window.innerWidth <= 992) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>