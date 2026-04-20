<?php
include 'koneksi.php';

if (isset($_POST['save'])) {
    $id_produk    = $_POST['id_produk'];
    $nama_produk  = $_POST['nama_produk'];
    $stok         = $_POST['stok'];
    $kategori     = $_POST['kategori'];
    $harga_jual   = $_POST['harga_jual'];

    // 1. LOGIKA UPLOAD FILE (Hanya di sini saja)
    $nama_file = ""; 
    if (!empty($_FILES['gambar_produk']['name'])) {
        $nama_file = time() . '_' . $_FILES['gambar_produk']['name']; // Memberi nama unik agar tidak tertukar
        $folder_tujuan = "uploads/";
        move_uploaded_file($_FILES['gambar_produk']['tmp_name'], $folder_tujuan . $nama_file);
    }

    // 2. PROSES SIMPAN/UPDATE
    if (empty($id_produk)) {
        // --- INSERT BARU ---
        $query = "INSERT INTO produk (nama_produk, harga_satuan, stok, kategori, gambar_produk) 
                  VALUES ('$nama_produk', '$harga_jual', '$stok', '$kategori', '$nama_file')";
    } else {
        // --- UPDATE ---
        if (!empty($nama_file)) {
            // Update dengan gambar baru
            $query = "UPDATE produk SET 
                      nama_produk = '$nama_produk', 
                      harga_satuan = '$harga_jual', 
                      stok = '$stok', 
                      kategori = '$kategori',
                      gambar_produk = '$nama_file' 
                      WHERE id_produk = '$id_produk'";
        } else {
            // Update tanpa mengubah gambar (tetap gunakan gambar lama)
            $query = "UPDATE produk SET 
                      nama_produk = '$nama_produk', 
                      harga_satuan = '$harga_jual', 
                      stok = '$stok', 
                      kategori = '$kategori' 
                      WHERE id_produk = '$id_produk'";
        }
    }

    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Data Berhasil Disimpan'); window.location='stok.php';</script>";
    } else {
        echo "Gagal: " . mysqli_error($koneksi);
    }
}

// --- HAPUS ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Opsional: Hapus file gambar dari folder sebelum hapus data di database
    mysqli_query($koneksi, "DELETE FROM produk WHERE id_produk = '$id'");
    echo "<script>alert('Data Berhasil Dihapus'); window.location='stok.php';</script>";
}
?>