<?php
include 'koneksi.php';

if (isset($_POST['save'])) {
    $id_produk    = $_POST['id_produk']; // Akan terisi jika mode Edit
    $nama_produk  = $_POST['nama_produk'];
    $stok         = $_POST['stok'];
    $kategori     = $_POST['kategori'];
    $harga_jual   = $_POST['harga_jual'];

    if (empty($id_produk)) {
        // --- LOGIKA TAMBAH PRODUK BARU ---
        $query = "INSERT INTO produk (nama_produk, harga_satuan, stok, kategori) 
                  VALUES ('$nama_produk', '$harga_jual', '$stok', '$kategori')";
    } else {
        // --- LOGIKA EDIT PRODUK LAMA ---
        $query = "UPDATE produk SET 
                  nama_produk = '$nama_produk', 
                  harga_satuan = '$harga_jual', 
                  stok = '$stok', 
                  kategori = '$kategori' 
                  WHERE id_produk = '$id_produk'";
    }

    $exec = mysqli_query($koneksi, $query);

    if ($exec) {
        echo "<script>alert('Data Berhasil Disimpan'); window.location='stok.php';</script>";
    } else {
        echo "Gagal menyimpan data: " . mysqli_error($koneksi);
    }
}
?>