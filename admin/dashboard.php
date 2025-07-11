<?php
session_start();

// Cek apakah pengguna sudah login dan memiliki peran 'admin'
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    // Jika tidak, arahkan ke halaman login
    header("location: ../login.php"); // Kembali ke halaman login utama
    exit;
}

// Anda bisa menambahkan logika untuk mengambil data ringkasan di sini
// Contoh: jumlah produk, jumlah pesanan, dll.
// require_once '../db_connect.php'; // Jika perlu koneksi DB
// $total_products = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
// $conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - BonekaKu</title>
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet"></head>
<body>
    <header>
        <div class="header-top">
            <span>Selamat datang, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</span>
            <div class="logo-kawaii">
        <img src="../assets/images/plushie.png" alt="Logo" />
            </div>
            <a href="../logout.php">Logout</a> </div>
        <nav class="navbar">
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_products.php">Produk</a>
                <a href="manage_categories.php">Kategori</a>
                <a href="manage_orders.php">Pesanan</a>
                <a href="manage_users.php">Pengguna</a>
                <a href="../index.php">Lihat Situs</a> </div>
        </nav>
    </header>

    <main class="main-content">
        <div class="admin-dashboard-container">
            <h2>Selamat Datang di Dashboard Admin</h2>
            <p class="welcome-message">Di sini Anda dapat mengelola semua aspek toko online BonekaKu.</p>

            <div class="admin-features-grid">
                <div class="feature-card">
                    <h3>Manajemen Produk</h3>
                    <p>Tambah, edit, atau hapus produk yang tersedia di toko.</p>
                    <a href="manage_products.php" class="btn-primary">Kelola Produk</a>
                </div>

                <div class="feature-card">
                    <h3>Manajemen Kategori</h3>
                    <p>Atur kategori untuk produk Anda.</p>
                    <a href="manage_categories.php" class="btn-primary">Kelola Kategori</a>
                </div>

                <div class="feature-card">
                    <h3>Manajemen Pesanan</h3>
                    <p>Lihat dan kelola pesanan pelanggan.</p>
                    <a href="manage_orders.php" class="btn-primary">Kelola Pesanan</a>
                </div>

                <div class="feature-card">
                    <h3>Manajemen Pengguna</h3>
                    <p>Kelola daftar pengguna dan hak akses mereka.</p>
                    <a href="manage_users.php" class="btn-primary">Kelola Pengguna</a>
                </div>
            </div>
        </div>
    </main>

      <footer class="footer">
  <div class="footer-container">
    <div class="footer-column">
      <h3>Support</h3>
      <ul>
        <li><a href="#">Contact us</a></li>
        <li><a href="#">Track Parcel</a></li>
        <li><a href="#">FAQs</a></li>
        <li><a href="#">Shipping Policy</a></li>
        <li><a href="#">Refund Policy</a></li>
        <li><a href="#">Payment</a></li>
      </ul>
    </div>
    <div class="footer-column">
      <h3>Learn</h3>
      <ul>
        <li><a href="#">About us</a></li>
        <li><a href="#">Our Blog</a></li>
        <li><a href="#">Meet our Ambassadors</a></li>
        <li><a href="#">Terms And Conditions</a></li>
        <li><a href="#">Our Promise Of Privacy</a></li>
      </ul>
    </div>
  </div>

  <div class="footer-social">
    <a href="#"><i class="fab fa-facebook"></i></a>
    <a href="#"><i class="fab fa-instagram"></i></a>
    <a href="#"><i class="fab fa-youtube"></i></a>
  </div>

  <div class="footer-bottom">
    <p>&copy; <?php echo date("Y"); ?> Toko Boneka Impian. Semua Hak Cipta Dilindungi.</p>
  </div>
</footer>
</body>
</html>