<?php
include 'koneksi.php';

// --- LOGIKA SIMPAN (TAMBAH & EDIT) ---
if (isset($_POST['save'])) {
    $id_produk    = $_POST['id_produk'];
    $nama_produk  = $_POST['nama_produk'];
    $stok         = $_POST['stok'];
    $kategori     = $_POST['kategori'];
    $harga_jual   = $_POST['harga_jual'];

    if (empty($id_produk)) {
        // Tambah baru
        $query = "INSERT INTO produk (nama_produk, harga_satuan, stok, kategori) 
                  VALUES ('$nama_produk', '$harga_jual', '$stok', '$kategori')";
    } else {
        // Edit lama
        $query = "UPDATE produk SET 
                  nama_produk = '$nama_produk', 
                  harga_satuan = '$harga_jual', 
                  stok = '$stok', 
                  kategori = '$kategori' 
                  WHERE id_produk = '$id_produk'";
    }

    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Data Berhasil Disimpan'); window.location='stok.php';</script>";
    } else {
        echo "Gagal: " . mysqli_error($koneksi);
    }
}

// --- LOGIKA HAPUS ---
// Kita cek apakah ada parameter 'hapus' di URL
// --- LOGIKA HAPUS ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $query = "DELETE FROM produk WHERE id_produk = '$id'";

    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Data Berhasil Dihapus'); window.location='stok.php';</script>";
    } else {
        echo "Gagal hapus: " . mysqli_error($koneksi);
    }
}
?>