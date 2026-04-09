<?php
include 'koneksi.php';
$id = $_GET['id'];


$query = mysqli_query($koneksi, "SELECT p.nama_produk, d.jumlah_produk, d.subtotal 
                                FROM detail_transaksi d 
                                JOIN produk p ON d.id_produk = p.id_produk 
                                WHERE d.id_transaksi = '$id'");

if(mysqli_num_rows($query) > 0) {
    while($row = mysqli_fetch_assoc($query)) {
        
        echo "<div class='item-row'>
                <span>{$row['nama_produk']} (x{$row['jumlah_produk']})</span>
                <span style='font-weight:700;'>Rp ".number_format($row['subtotal'], 0, ',', '.')."</span>
              </div>";
    }
} else {
    echo "Detail produk tidak ditemukan.";
}
?>