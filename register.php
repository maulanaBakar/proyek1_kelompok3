<?php
include 'koneksi.php';
?>

if (isset($_POST['register'])) {

    $nama_admin = mysqli_real_escape_string($koneksi, $_POST['nama_admin']);
    $email      = mysqli_real_escape_string($koneksi, $_POST['email']);
    $no_hp      = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $password   = mysqli_real_escape_string($koneksi, $_POST['password']);
    

    if (empty($nama_admin) || empty($email) || empty($password) || empty($no_hp)) {
        echo "<script>alert('Data dalam form kurang !'); window.history.back();</script>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $cek = mysqli_query($koneksi, "SELECT * FROM admin WHERE email='$email'");
        if (mysqli_num_rows($cek) > 0) {
            echo "<script>alert('Email sudah terdaftar!'); window.history.back();</script>";
        } else {

            $query = "INSERT INTO admin (nama_admin, email, password, no_hp) 
                      VALUES ('$nama_admin', '$email', '$password', '$no_hp')";
            
            if (mysqli_query($koneksi, $query)) {
                echo "<script>alert('Registrasi Berhasil! Silakan Login.'); window.location='login.php';</script>";
            } else {
                echo "Error: " . mysqli_error($koneksi);
            }
        }
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar - 2 Paksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
     <link rel="stylesheet" href="styleregister.css" />
</head>
<body>
    <div class="main-card">
        <div class="left-side">
            <div class="brand">2 Paksi</div>
            <p class="text-muted small">Lengkapi data admin produksi.</p>
            <form action="" method="POST">
                <div class="mb-2">
                    <label class="small fw-bold">Nama Lengkap</label>
                    <input type="text" name="nama_admin" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="small fw-bold">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="small fw-bold">No. HP</label>
                    <input type="text" name="no_hp" class="form-control" placeholder="08..." required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" name="register" class="btn-reg">Daftar Sekarang</button>
                <p class="text-center mt-3 small">Sudah punya akun? <a href="login.php" style="color: #967e76; text-decoration: none; font-weight: bold;">Login</a></p>
            </form>
        </div>
        <div class="right-side">
            <img src="img/login-bg.jpg" alt="Background">
        </div>
    </div>
</body>
</html>
