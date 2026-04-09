// MENU TOGGLE HP
const menuBtn = document.getElementById("menu-btn");
const sidebar = document.getElementById("sidebar");

menuBtn.addEventListener("click", () => {
  sidebar.classList.toggle("active");
});

// Tutup menu saat klik luar sidebar (HP)
document.addEventListener("click", (e) => {
  if (
    !sidebar.contains(e.target) &&
    !menuBtn.contains(e.target) &&
    window.innerWidth <= 992
  ) {
    sidebar.classList.remove("active");
  }
});

// MODAL HANDLER
function toggleModal(
  mode,
  id = "",
  nama = "",
  stok = "",
  kat = "",
  harga = "",
) {
  const modal = document.getElementById("modalProduk");
  if (!mode) {
    modal.style.display = "none";
    return;
  }

  modal.style.display = "flex";
  document.getElementById("mId").value = id;
  document.getElementById("mNama").value = nama;
  document.getElementById("mStok").value = stok;
  document.getElementById("mKat").value = kat;
  document.getElementById("mHarga").value = harga;

  const mTitle = document.getElementById("mTitle");
  const mBtn = document.getElementById("mBtn");

  if (mode === "edit") {
    mTitle.innerText = "Edit Produk";
    mBtn.innerText = "Simpan Perubahan";
    mBtn.style.background = "#27ae60";
    mBtn.style.boxShadow = "0 4px 12px rgba(39, 174, 96, 0.15)";
  } else {
    mTitle.innerText = "Tambah Produk Baru";
    mBtn.innerText = "Tambah Produk";
    mBtn.style.background = "#4A3E3D";
    mBtn.style.boxShadow = "0 4px 12px rgba(74, 62, 61, 0.15)";
  }
}
