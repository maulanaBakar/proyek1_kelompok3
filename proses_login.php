<?php
session_start();
include 'koneksi.php';

if (isset($_POST['email'])) { 
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];

  
    $query = mysqli_query($koneksi, "SELECT * FROM admin WHERE email='$email'");
    
    if (mysqli_num_rows($query) === 1) {
        $data = mysqli_fetch_assoc($query);
        
        // Verifikasi password hash
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