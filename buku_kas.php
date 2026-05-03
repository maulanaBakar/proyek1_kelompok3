<?php
include 'koneksi.php';
include 'tgl_indo.php';
session_start();

if($_SESSION['status'] != "login") header("location:login.php");

if(isset($_POST['simpan_kas'])) {
    $jenis = $_POST['jenis'];
    $ket = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    $nom = (int)str_replace('.', '', $_POST['nominal']); 
    $tgl = date("Y-m-d H:i:s");

    mysqli_query($koneksi, "INSERT INTO buku_kas VALUES (NULL, '$tgl', '$ket', '$jenis', '$nom')");
    header("location:buku_kas.php");
}

if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM buku_kas WHERE id_buku_kas = '$id'");
    header("location:buku_kas.php");
}

$m = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(nominal) as t FROM buku_kas WHERE jenis='Pemasukan'"))['t'] ?? 0;
$k = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(nominal) as t FROM buku_kas WHERE jenis='Pengeluaran'"))['t'] ?? 0;
$saldo = $m - $k;

$q_toko = mysqli_query($koneksi, "SELECT nama_toko FROM pengaturan_toko WHERE id=1");
$d_toko = mysqli_fetch_assoc($q_toko);
$nama_toko = $d_toko['nama_toko'] ?? "2 PAKSI";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Buku Kas - <?= $nama_toko ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="dashboard.css">
    <style>
        body {
            font-family: "Plus Jakarta Sans", sans-serif;
        }

        .link-menu {
            font-weight: 600 !important;
        }

        .kas-wrapper {
             display: flex; 
             gap: 20px; 
             padding: 20px; 
        }
        .side-form {
             width: 350px; 
             flex-shrink: 0;
        }
        .main-table {
             flex-grow: 1; 
             background: white;
             border-radius: 12px; 
             padding: 20px; 
             border: 1px solid #eee; 
        }
        
        .card-saldo {
             background: #4a3e3d; 
             color: white; 
             padding: 25px;
             border-radius: 12px; 
             margin-bottom: 20px;
             text-align: center;
        }
        .box-input { 
            background: white;
            padding: 20px; 
            border-radius: 12px; 
            border: 1px solid #eee;
        }
        
        input, select { 
            width: 100%; 
            padding: 12px; 
            margin: 8px 0 15px;
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-family: inherit; 
        }
        .btn-add {
            width: 100%; 
            padding: 12px;
            background: #27ae60; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            font-weight: bold; 
            cursor: pointer; 
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        th {
            text-align: left;
            padding: 12px; 
            color: #888; 
            border-bottom: 2px solid #eee; 
            font-size: 13px;
        }
        td { 
            padding: 12px; 
            border-bottom: 1px solid #f9f9f9;
            font-size: 14px;
        }
        
        .m-text { color: #27ae60; font-weight: bold; }
        .k-text { color: #e74c3c; font-weight: bold; }
        .badge { 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-size: 11px; 
            text-transform: uppercase;
            font-weight: 800;
        }
        .b-m { background: #e8f8f5; color: #27ae60; }
        .b-k { background: #ffebee; color: #e74c3c; }

        @media (max-width: 900px) {
            .kas-wrapper {
                flex-direction: column;
            }
            .side-form {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <input type="checkbox" id="check-menu">

    <div class="bar-atas-mobile">
        <div class="nama-toko"><?= $nama_toko ?></div>
        <label for="check-menu" class="tombol-buka"><i class="fa-solid fa-bars"></i></label>
    </div>

    <aside class="menu-samping">
        <div class="bagian-atas">
            <div class="judul-logo"><?= $nama_toko ?></div>
            <nav class="daftar-menu">
                <a href="dashboard.php" class="link-menu"><i class="fa-solid fa-house"></i> Beranda</a>
                <a href="kasir.php" class="link-menu"><i class="fa-solid fa-cash-register"></i> Kasir</a>
                <a href="stok.php" class="link-menu"><i class="fa-solid fa-box"></i> Stok Barang</a>
                <a href="buku_kas.php" class="link-menu aktif"><i class="fa-solid fa-wallet"></i> Buku Kas</a>
                <a href="laporan.php" class="link-menu"><i class="fa-solid fa-file-lines"></i> Laporan</a>
                <a href="pengaturan.php" class="link-menu"><i class="fa-solid fa-gear"></i> Pengaturan</a>
            </nav>
        </div>
        <div class="bagian-bawah">
            <a href="logout.php" class="link-menu keluar"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a>
        </div>
    </aside>

    <main class="isi-halaman">
        <header class="judul-halaman">
            <h1>Manajemen Buku Kas</h1>
        </header>

        <div class="card-saldo">
            <span style="opacity: 0.8; font-size: 0.9em; font-weight: 600;">SALDO LACI SAAT INI</span>
            <h1 style="font-size: 2.5rem; margin: 10px 0; font-weight: 800;">Rp <?= number_format($saldo, 0, ',', '.') ?></h1>
        </div>

        <div class="kas-wrapper">
            <div class="side-form">
                <div class="box-input">
                    <h3 style="margin-bottom: 20px; font-weight: 800;">Catat Transaksi</h3>
                    <form action="" method="POST">
                        <label class="label-kecil">JENIS TRANSAKSI</label>
                        <select name="jenis">
                            <option value="Pemasukan">Pemasukan (+)</option>
                            <option value="Pengeluaran">Pengeluaran (-)</option>
                        </select>
                        <label class="label-kecil">KETERANGAN</label>
                        <input type="text" name="keterangan" placeholder="Contoh: Bayar Listrik" required>
                        <label class="label-kecil">NOMINAL (RP)</label>
                        <input type="text" name="nominal" id="rp" onkeyup="formatRp(this)" placeholder="0" required>
                        <button type="submit" name="simpan_kas" class="btn-add">SIMPAN TRANSAKSI</button>
                    </form>
                </div>
            </div>

            <div class="main-table">
                <h3 style="margin-bottom: 20px; font-weight: 800;">Riwayat Kas Terakhir</h3>
                <table>
                    <thead>
                        <tr>
                            <th>TANGGAL</th>
                            <th>KETERANGAN</th>
                            <th>JENIS</th>
                            <th>NOMINAL</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res = mysqli_query($koneksi, "SELECT * FROM buku_kas ORDER BY tanggal DESC LIMIT 20");
                        while($row = mysqli_fetch_assoc($res)){
                            $isM = $row['jenis'] == 'Pemasukan';
                        ?>
                        <tr>
                            <td style="color: #888;"><?= date('d/m H:i', strtotime($row['tanggal'])) ?></td>
                            <td><strong style="color: var(--cokelat-tua);"><?= htmlspecialchars($row['keterangan']) ?></strong></td>
                            <td><span class="badge <?= $isM ? 'b-m':'b-k' ?>"><?= $row['jenis'] ?></span></td>
                            <td class="<?= $isM ? 'm-text':'k-text' ?>"><?= $isM ? '+':'-' ?> <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                            <td>
                                <a href="?hapus=<?= $row['id_buku_kas'] ?>" onclick="return confirm('Hapus data ini?')" style="color:#ccc; transition: 0.3s;" onmouseover="this.style.color='#e74c3c'" onmouseout="this.style.color='#ccc'">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
    function formatRp(obj){
        let val = obj.value.replace(/[^,\d]/g, "").toString();
        let split = val.split(",");
        let sisa = split[0].length % 3;
        let idr = split[0].substr(0, sisa);
        let ribu = split[0].substr(sisa).match(/\d{3}/gi);
        if(ribu){
            let sep = sisa ? "." : "";
            idr += sep + ribu.join(".");
        }
        obj.value = idr;
    }
    </script>
</body>
</html>