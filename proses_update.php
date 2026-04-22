<?php
session_start();
include 'koneksi.php';

if (isset($_POST['submit'])) {
    $id = $_SESSION['id']; // Pastikan session ID ada
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Logika Update Foto (Mencegah Eksekusi File Berbahaya)
    $foto_baru = null;
    if (!empty($_FILES['foto']['name'])) {
        $file_name = $_FILES['foto']['name'];
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        // Cek ekstensi file
        if (in_array($file_ext, $allowed)) {
            $nama_acak = uniqid() . '.' . $file_ext; // Mengacak nama file agar aman
            move_uploaded_file($file_tmp, "uploads/" . $nama_acak);
            $foto_baru = $nama_acak;
        } else {
            die("Format file tidak diizinkan!");
        }
    }

    // 2. Update Data (Menggunakan Prepared Statements)
    try {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username=?, email=?, password=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $params = [$username, $email, $hashed_password, $id];
        } else {
            $sql = "UPDATE users SET username=?, email=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $params = [$username, $email, $id];
        }

        // Jika ada foto baru, tambahkan ke query
        if ($foto_baru) {
            $sql = str_replace("WHERE id=?", ", foto=? WHERE id=?", $sql);
            $params = [$username, $email, $foto_baru, $id]; // Sesuaikan urutan
            $stmt = $pdo->prepare($sql);
        }

        $stmt->execute($params);
        header("location:pengaturan.php?status=sukses");
        
    } catch (PDOException $e) {
        echo "Gagal update: " . $e->getMessage();
    }
}
?>