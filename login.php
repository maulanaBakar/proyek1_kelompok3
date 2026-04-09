<?php
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) { 
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];

    $query = mysqli_query($koneksi, "SELECT * FROM admin WHERE email='$email'");
    
    if (mysqli_num_rows($query) === 1) {
        $data = mysqli_fetch_assoc($query);
        
        if (password_verify($password, $data['password'])) {
            $_SESSION['admin'] = $data['username'];
            $_SESSION['status'] = "login"; 
            header("Location: dashboard.php"); 
            exit();
        } else {
            echo "<script>alert('Password salah!'); window.location='login.php';</script>";
        }
    } else {
        echo "<script>alert('Email tidak terdaftar!'); window.location='login.php';</script>";
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - 2 Paksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css" />
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="brand-name">2 Paksi</div>
            <p class="text-muted small mb-4">Silakan masuk ke akun admin Anda.</p>
            
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="example@gmail.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" name="login" class="btn-login">Login</button>
            </form>
            
            <div class="text-center mt-4">
                <small>Belum punya akun? <a href="register.php" style="color: #967e76; text-decoration: none; font-weight: 600;">Daftar di sini</a></small>
            </div>
        </div>
        
        <div class="login-image">
            <img src="img/bg.png" alt="2 Paksi Production">
        </div>
    </div>
</body>
</html>