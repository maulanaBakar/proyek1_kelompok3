<?php 
session_start();
include 'koneksi.php'; 

// Cek sesi login
if($_SESSION['status'] != "login"){
    header("location:login.php");
    exit();
}

$id_user = $_SESSION['id_admin'] ?? 1; 
$nama_tabel = "admin"; 

$pesan_akun = "";
$pesan_toko = "";

// ==========================================
// 1. PROSES UPDATE AKUN
// ==========================================
if (isset($_POST['simpan_akun'])) {
    $nama_admin = mysqli_real_escape_string($koneksi, $_POST['nama_admin']);
    $email      = mysqli_real_escape_string($koneksi, $_POST['email']);
    $no_hp      = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $pass_baru  = $_POST['password'];

    if (!empty($pass_baru)) {
        $password_hash = password_hash($pass_baru, PASSWORD_DEFAULT);
        $update = mysqli_query($koneksi, "UPDATE $nama_tabel SET nama_admin='$nama_admin', email='$email', no_hp='$no_hp', password='$password_hash' WHERE id_admin='$id_user'");
    } else {
        $update = mysqli_query($koneksi, "UPDATE $nama_tabel SET nama_admin='$nama_admin', email='$email', no_hp='$no_hp' WHERE id_admin='$id_user'");
    }

    if ($update) {
        $pesan_akun = "<div class='alert-sukses'><i class='fa-solid fa-check-circle'></i> Data akun berhasil diperbarui!</div>";
    } else {
        $pesan_akun = "<div class='alert-gagal'><i class='fa-solid fa-circle-xmark'></i> Gagal update akun: " . mysqli_error($koneksi) . "</div>";
    }
}

// ==========================================
// 2. PROSES UPDATE TOKO
// ==========================================
if (isset($_POST['simpan_toko'])) {
    $nama_toko     = mysqli_real_escape_string($koneksi, $_POST['nama_toko']);
    $alamat        = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $telepon       = mysqli_real_escape_string($koneksi, $_POST['telepon']);

    $update_toko = mysqli_query($koneksi, "UPDATE pengaturan_toko SET nama_toko='$nama_toko', alamat='$alamat', telepon='$telepon' WHERE id=1");

    if ($update_toko) {
        $pesan_toko = "<div class='alert-sukses'><i class='fa-solid fa-check-circle'></i> Profil toko berhasil diperbarui!</div>";
    } else {
        $pesan_toko = "<div class='alert-gagal'><i class='fa-solid fa-circle-xmark'></i> Gagal update toko.</div>";
    }
}

// ==========================================
// AMBIL DATA UNTUK DITAMPILKAN DI FORM
// ==========================================
$query_user = mysqli_query($koneksi, "SELECT * FROM $nama_tabel WHERE id_admin='$id_user'");
$data_akun = mysqli_fetch_assoc($query_user);

$query_toko = mysqli_query($koneksi, "SELECT * FROM pengaturan_toko WHERE id=1");
$data_toko = mysqli_fetch_assoc($query_toko);
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pengaturan - 2 Paksi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .pengaturan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .kotak-pengaturan {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            border-top: 4px solid var(--cokelat-muda, #d4a373);
            position: relative;
        }
        .kotak-pengaturan h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.3em;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 0.9em; }
        .form-group input, .form-group textarea { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-family: inherit;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        /* EFEK KETIKA FORM TERKUNCI (READONLY) */
        .form-group input[readonly], .form-group textarea[readonly] {
            background-color: #f4f6f8;
            color: #777;
            border-color: transparent;
            cursor: not-allowed;
        }
        
        /* EFEK KETIKA FORM BISA DIEDIT */
        .form-group input:not([readonly]):focus, .form-group textarea:not([readonly]):focus {
            border-color: var(--cokelat-muda, #d4a373);
            outline: none;
            box-shadow: 0 0 0 3px rgba(212, 163, 115, 0.2);
            background-color: #fff;
        }

        /* TOMBOL BISA DI-TOGGLE */
        .grup-tombol {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-edit {
            background: #f1c40f; 
            color: #333; 
            padding: 14px 20px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            width: 100%; 
            font-weight: 600;
        }
        .btn-edit:hover { background: #d4ac0d; }

        .btn-simpan { 
            background: var(--cokelat-tua, #4a3e3d); 
            color: white; 
            padding: 14px 20px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            width: 100%; 
            font-weight: 600;
            display: none; /* Disembunyikan awalnya */
        }
        .btn-simpan:hover { background: #332a29; }
        
        .btn-batal {
            background: #e74c3c;
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: none; /* Disembunyikan awalnya */
        }
        .btn-batal:hover { background: #c0392b; }

        .alert-sukses { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9em; }
        .alert-gagal { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9em; }
    </style>
</head>
<body>

    <input type="checkbox" id="check-menu">
    <div class="bar-atas-mobile">
        <div class="nama-toko"><?= htmlspecialchars($data_toko['nama_toko'] ?? '2 PAKSI') ?></div>
        <label for="check-menu" class="tombol-buka"><i class="fa-solid fa-bars"></i></label>
    </div>

    <aside class="menu-samping">
        <div class="bagian-atas">
            <div class="judul-logo"><?= htmlspecialchars($data_toko['nama_toko'] ?? '2 PAKSI') ?></div>
            <nav class="daftar-menu">
                <a href="dashboard.php" class="link-menu"><i class="fa-solid fa-house"></i> Beranda</a>
                <a href="kasir.php" class="link-menu"><i class="fa-solid fa-cash-register"></i> Kasir</a>
                <a href="stok.php" class="link-menu"><i class="fa-solid fa-box"></i> Stok Barang</a>
                <a href="laporan.php" class="link-menu"><i class="fa-solid fa-file-lines"></i> Laporan</a>
                <a href="pengaturan.php" class="link-menu aktif"><i class="fa-solid fa-gear"></i> Pengaturan</a>
            </nav>
        </div>
        <div class="bagian-bawah">
            <a href="logout.php" class="link-menu keluar"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a>
        </div>
    </aside>

    <main class="isi-halaman">
        <header class="judul-halaman">
            <h1>Pengaturan Sistem</h1>
        </header>

        <div class="pengaturan-grid">
            <div class="kotak-pengaturan">
                <h2><i class="fa-solid fa-user-shield" style="color: #3498db;"></i> Pengaturan Akun</h2>
                <?= $pesan_akun; ?>
                <form method="POST" id="form-akun">
                    <div class="form-group">
                        <label>Nama Lengkap (Admin)</label>
                        <div style="position: relative;">
                            <i class="fa-solid fa-user" style="position: absolute; left: 15px; top: 15px; color: #888;"></i>
                            <input type="text" name="nama_admin" value="<?= htmlspecialchars($data_akun['nama_admin'] ?? ''); ?>" style="padding-left: 45px;" required readonly>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Email / Akun Google</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($data_akun['email'] ?? ''); ?>" required readonly>
                        </div>
                        <div class="form-group">
                            <label>No. HP</label>
                            <input type="text" name="no_hp" value="<?= htmlspecialchars($data_akun['no_hp'] ?? ''); ?>" required readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Password Baru <small style="font-weight: 400; color: #888;">(Biarkan kosong jika tidak ganti)</small></label>
                        <div style="position: relative;">
                            <i class="fa-solid fa-lock" style="position: absolute; left: 15px; top: 15px; color: #888;"></i>
                            <input type="password" name="password" placeholder="Terkunci..." style="padding-left: 45px;" readonly>
                        </div>
                    </div>
                    
                    <div class="grup-tombol">
                        <button type="button" id="btn-edit-akun" class="btn-edit" onclick="aktifkanEdit('akun')"><i class="fa-solid fa-pen-to-square"></i> Edit Akun</button>
                        <button type="button" id="btn-batal-akun" class="btn-batal" onclick="batalkanEdit('akun')"><i class="fa-solid fa-xmark"></i> Batal</button>
                        <button type="submit" name="simpan_akun" id="btn-simpan-akun" class="btn-simpan"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                    </div>
                </form>
            </div>

            <div class="kotak-pengaturan">
                <h2><i class="fa-solid fa-store" style="color: var(--cokelat-muda);"></i> Identitas Toko</h2>
                <?= $pesan_toko; ?>
                <form method="POST" id="form-toko">
                    <div class="form-group">
                        <label>Nama Toko</label>
                        <input type="text" name="nama_toko" value="<?= htmlspecialchars($data_toko['nama_toko'] ?? ''); ?>" required readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>No. Telepon / WhatsApp Toko</label>
                        <input type="text" name="telepon" value="<?= htmlspecialchars($data_toko['telepon'] ?? ''); ?>" required readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Alamat Lengkap Toko</label>
                        <textarea name="alamat" rows="4" required readonly><?= htmlspecialchars($data_toko['alamat'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="grup-tombol">
                        <button type="button" id="btn-edit-toko" class="btn-edit" onclick="aktifkanEdit('toko')"><i class="fa-solid fa-pen-to-square"></i> Edit Toko</button>
                        <button type="button" id="btn-batal-toko" class="btn-batal" onclick="batalkanEdit('toko')"><i class="fa-solid fa-xmark"></i> Batal</button>
                        <button type="submit" name="simpan_toko" id="btn-simpan-toko" class="btn-simpan"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function aktifkanEdit(tipe) {
            // Hilangkan atribut readonly dari semua input dan textarea di form yang dipilih
            let form = document.getElementById('form-' + tipe);
            let inputs = form.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                input.removeAttribute('readonly');
            });

            // Ubah placeholder password agar tahu bisa diketik
            if(tipe === 'akun') {
                form.querySelector('input[name="password"]').placeholder = 'Ketik password baru...';
            }

            // Atur tampilan tombol
            document.getElementById('btn-edit-' + tipe).style.display = 'none';
            document.getElementById('btn-batal-' + tipe).style.display = 'block';
            document.getElementById('btn-simpan-' + tipe).style.display = 'block';
        }

        function batalkanEdit(tipe) {
            // Karena dibatalkan, kita reload saja halamannya agar data kembali ke awal
            window.location.href = 'pengaturan.php';
        }
    </script>

</body>
</html>