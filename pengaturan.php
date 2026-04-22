<?php 
session_start();
include 'koneksi.php'; 

// 1. SESUAIKAN KEY SESSION
// Cek di login.php, apa nama session yang Anda simpan? 
// Biasanya $_SESSION['id'] atau $_SESSION['id_admin']
$id_user = $_SESSION['id']; 

// 2. NAMA TABEL ADALAH 'admin' (Sesuai register.php)
$nama_tabel = "admin"; 

if($_SESSION['status'] != "login"){
    header("location:login.php");
    exit();
}

$pesan = "";

// PROSES UPDATE
if (isset($_POST['btn_simpan'])) {
    // Sesuai dengan kolom di tabel admin (nama_admin, email)
    $nama_admin = mysqli_real_escape_string($koneksi, $_POST['nama_admin']);
    $email      = mysqli_real_escape_string($koneksi, $_POST['email']);
    $pass_baru  = $_POST['password'];

    if (!empty($pass_baru)) {
        $password_hash = password_hash($pass_baru, PASSWORD_DEFAULT);
        // Update password juga
        $update = mysqli_query($koneksi, "UPDATE $nama_tabel SET nama_admin='$nama_admin', email='$email', password='$password_hash' WHERE id='$id_user'");
    } else {
        // Update tanpa password
        $update = mysqli_query($koneksi, "UPDATE $nama_tabel SET nama_admin='$nama_admin', email='$email' WHERE id='$id_user'");
    }

    if ($update) {
        $pesan = "<p style='color:green;'>Data berhasil diperbarui!</p>";
    } else {
        $pesan = "<p style='color:red;'>Gagal update: " . mysqli_error($koneksi) . "</p>";
    }
}

// AMBIL DATA
$query_user = mysqli_query($koneksi, "SELECT * FROM $nama_tabel WHERE id='$id_user'");
$data = mysqli_fetch_assoc($query_user);
?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <title>Pengaturan - 2 Paksi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .main-content { margin-left: 260px; padding: 40px; }
        .kotak-pengaturan { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 500px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-simpan { background: #4a3e3d; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; width: 100%; }
    </style>
  </head>
  <body>
    <aside class="menu-samping">
      <div class="bagian-atas">
        <div class="judul-logo">2 PAKSI</div>
        <nav class="daftar-menu">
          <a href="dashboard.php" class="link-menu"><i class="fa-solid fa-house"></i> Beranda</a>
          <a href="pengaturan.php" class="link-menu aktif"><i class="fa-solid fa-gear"></i> Pengaturan</a>
        </nav>
      </div>
    </aside>

    <main class="main-content">
        <h2>Pengaturan Akun</h2>
        <div class="kotak-pengaturan">
            <?php echo $pesan; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_admin" value="<?php echo htmlspecialchars($data['nama_admin']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($data['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Password Baru <small>(Kosongkan jika tidak ganti)</small></label>
                    <input type="password" name="password" placeholder="Masukkan password baru...">
                </div>
                <button type="submit" name="btn_simpan" class="btn-simpan">Simpan Perubahan</button>
            </form>
        </div>
    </main>
  </body>
</html>