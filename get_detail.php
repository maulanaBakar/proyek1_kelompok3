<?php
include 'koneksi.php';

if(isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // Ambil detail produk
    $query = mysqli_query($koneksi, "SELECT dt.*, p.nama_produk 
                                     FROM detail_transaksi dt 
                                     JOIN produk p ON dt.id_produk = p.id_produk 
                                     WHERE dt.id_transaksi = '$id'");
    
    // Tambahkan query untuk hitung total transaksi
    $q_total = mysqli_query($koneksi, "SELECT total_pendapatan FROM transaksi WHERE id_transaksi = '$id'");
    $data_total = mysqli_fetch_assoc($q_total);

    if(mysqli_num_rows($query) > 0) {
        while ($d = mysqli_fetch_assoc($query)) {
            echo "
            <div class='item-row'>
                <div class='item-info'>
                    <span class='nama'>{$d['nama_produk']}</span>
                    <span class='qty'>Jumlah: {$d['jumlah_produk']}</span>
                </div>
                <span class='item-price'>Rp " . number_format($d['subtotal'], 0, ',', '.') . "</span>
            </div>";
        }
        
        // Menampilkan Total di bawah
        echo "
        <div class='total-nota'>
            <span>TOTAL</span>
            <span>Rp " . number_format($data_total['total_pendapatan'], 0, ',', '.') . "</span>
        </div>";

    } else {
        echo "<p style='text-align:center;'>Data tidak ditemukan.</p>";
    }
}
?>