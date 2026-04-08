<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "proyek_2paksi"; 
$user = "AMANDA";
$pass = "12345";
$db   = "proyek_2paksi"


$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>