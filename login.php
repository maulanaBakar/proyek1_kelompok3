<?php 
if(isset($_GET['pesan'])){
    if($_GET['pesan'] == "gagal"){
        echo "<div class='alert alert-danger'>Login gagal! Email atau password salah.</div>";
    } else if($_GET['pesan'] == "belum_login"){
        echo "<div class='alert alert-warning'>Anda harus login dulu.</div>";
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
            
            <form action="proses_login.php" method="POST">
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