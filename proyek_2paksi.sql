-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 09 Apr 2026 pada 04.15
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `proyek_2paksi`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `nama_admin` varchar(50) NOT NULL,
  `email` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id_admin`, `nama_admin`, `email`, `password`, `no_hp`) VALUES
(1, 'Eka Fadilla', 'ekafadilla2006@gmail.com', '$2y$10$FIERp.HWofX2521FAjabsuXdZ3VU4AyHBoiCiUpcO9eoyZPNd/wuO', '083101247752'),
(2, 'amanda', 'amanda@gmail.com', '$2y$10$QrSYMeIJEptz4gkp6/koK.Qr0/nEjBcCWSGv6LAQ7/Uot8x1uvaLm', '2345y6789');

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id_detail` int(11) NOT NULL,
  `id_transaksi` int(11) DEFAULT NULL,
  `id_produk` int(11) DEFAULT NULL,
  `jumlah_produk` int(11) DEFAULT NULL,
  `subtotal` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_transaksi`
--

INSERT INTO `detail_transaksi` (`id_detail`, `id_transaksi`, `id_produk`, `jumlah_produk`, `subtotal`) VALUES
(1, 8, 1, 2, 34000),
(2, 9, 1, 3, 51000),
(3, 10, 1, 1, 17000),
(4, 11, 1, 1, 17000),
(5, 12, 1, 1, 17000),
(6, 13, 1, 1, 17000),
(7, 14, 1, 1, 17000),
(8, 15, 2, 1, 19000),
(9, 15, 1, 1, 17000),
(10, 16, 1, 1, 17000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id_produk` int(11) NOT NULL,
  `nama_produk` varchar(30) NOT NULL,
  `harga_satuan` int(11) NOT NULL,
  `stok` varchar(40) NOT NULL,
  `kategori` varchar(20) DEFAULT NULL,
  `gambar_produk` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id_produk`, `nama_produk`, `harga_satuan`, `stok`, `kategori`, `gambar_produk`) VALUES
(1, 'udang', 17000, '8', 'kerupuk udang', NULL),
(2, 'kerupuk putih', 19000, '89', 'kerupuk udang', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `tanggal_transaksi` varchar(30) DEFAULT NULL,
  `total_pendapatan` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `id_admin`, `tanggal_transaksi`, `total_pendapatan`) VALUES
(1, 1, '2026-03-28 18:14:52', 17000),
(2, 1, '2026-03-28 18:15:00', 17000),
(3, 1, '2026-03-28 18:15:04', 17000),
(4, 1, '2026-03-28 18:19:11', 17000),
(5, 1, '2026-03-28 18:21:36', 17000),
(6, 1, '2026-03-28 18:23:51', 34000),
(7, 1, '2026-03-28 18:31:37', 34000),
(8, 1, '2026-03-28 18:32:21', 34000),
(9, 1, '2026-03-28 19:05:02', 51000),
(10, 1, '2026-03-28 19:24:27', 17000),
(11, 1, '2026-03-28 19:27:02', 17000),
(12, 1, '2026-03-28 19:31:32', 17000),
(13, 1, '2026-03-29 19:29:28', 17000),
(14, 1, '2026-03-30 15:39:51', 17000),
(15, 1, '2026-03-30 15:44:53', 36000),
(16, 1, '2026-04-07 05:48:00', 17000);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_laporan_lengkap`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_laporan_lengkap` (
`id_transaksi` int(11)
,`tanggal_transaksi` varchar(30)
,`tahun` int(4)
,`bulan_angka` int(2)
,`nama_bulan` varchar(9)
,`tgl_saja` date
,`total_pendapatan` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_produk_terlaris`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_produk_terlaris` (
`nama_produk` varchar(30)
,`gambar_produk` varchar(255)
,`total_terjual` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_rekapan_bulanan`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_rekapan_bulanan` (
`bulan` varchar(7)
,`total_bulanan` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_rekapan_harian`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_rekapan_harian` (
`tanggal_transaksi` varchar(30)
,`pendapatan_harian` decimal(32,0)
,`total_transaksi` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_rekapan_tahunan`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_rekapan_tahunan` (
`tahun` varchar(4)
,`total_pendapatan_tahunan` decimal(32,0)
,`jumlah_transaksi_setahun` bigint(21)
);

-- --------------------------------------------------------

--
-- Struktur untuk view `v_laporan_lengkap`
--
DROP TABLE IF EXISTS `v_laporan_lengkap`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_laporan_lengkap`  AS SELECT `transaksi`.`id_transaksi` AS `id_transaksi`, `transaksi`.`tanggal_transaksi` AS `tanggal_transaksi`, year(`transaksi`.`tanggal_transaksi`) AS `tahun`, month(`transaksi`.`tanggal_transaksi`) AS `bulan_angka`, monthname(`transaksi`.`tanggal_transaksi`) AS `nama_bulan`, cast(`transaksi`.`tanggal_transaksi` as date) AS `tgl_saja`, `transaksi`.`total_pendapatan` AS `total_pendapatan` FROM `transaksi` ;

-- --------------------------------------------------------

--
-- Struktur untuk view `v_produk_terlaris`
--
DROP TABLE IF EXISTS `v_produk_terlaris`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_produk_terlaris`  AS SELECT `p`.`nama_produk` AS `nama_produk`, `p`.`gambar_produk` AS `gambar_produk`, sum(`dt`.`jumlah_produk`) AS `total_terjual` FROM (`detail_transaksi` `dt` join `produk` `p` on(`dt`.`id_produk` = `p`.`id_produk`)) GROUP BY `p`.`id_produk`, `p`.`nama_produk`, `p`.`gambar_produk` ORDER BY sum(`dt`.`jumlah_produk`) DESC ;

-- --------------------------------------------------------

--
-- Struktur untuk view `v_rekapan_bulanan`
--
DROP TABLE IF EXISTS `v_rekapan_bulanan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_rekapan_bulanan`  AS SELECT date_format(`transaksi`.`tanggal_transaksi`,'%Y-%m') AS `bulan`, sum(`transaksi`.`total_pendapatan`) AS `total_bulanan` FROM `transaksi` GROUP BY date_format(`transaksi`.`tanggal_transaksi`,'%Y-%m') ;

-- --------------------------------------------------------

--
-- Struktur untuk view `v_rekapan_harian`
--
DROP TABLE IF EXISTS `v_rekapan_harian`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_rekapan_harian`  AS SELECT `transaksi`.`tanggal_transaksi` AS `tanggal_transaksi`, sum(`transaksi`.`total_pendapatan`) AS `pendapatan_harian`, count(`transaksi`.`id_transaksi`) AS `total_transaksi` FROM `transaksi` GROUP BY `transaksi`.`tanggal_transaksi` ;

-- --------------------------------------------------------

--
-- Struktur untuk view `v_rekapan_tahunan`
--
DROP TABLE IF EXISTS `v_rekapan_tahunan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_rekapan_tahunan`  AS SELECT date_format(`transaksi`.`tanggal_transaksi`,'%Y') AS `tahun`, sum(`transaksi`.`total_pendapatan`) AS `total_pendapatan_tahunan`, count(`transaksi`.`id_transaksi`) AS `jumlah_transaksi_setahun` FROM `transaksi` GROUP BY date_format(`transaksi`.`tanggal_transaksi`,'%Y') ;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_transaksi` (`id_transaksi`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_admin` (`id_admin`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `detail_transaksi_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_transaksi_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
