<?php
// Pastikan untuk memulai session
session_start();

// Include file koneksi database
require_once 'db_connect.php'; 

// Inisialisasi variabel untuk status login dan username
$loggedin = false;
$username = "Tamu"; 
$user_role = "guest"; // Default role untuk pengunjung

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $loggedin = true;
    $username = htmlspecialchars($_SESSION["username"]); // Ambil username dari sesi
    $user_role = $_SESSION["role"]; // Ambil role dari sesi
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Boneka Online - Beranda</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>
    <!-- TOP HEADER -->
<div class="header-top">

    <div class="logo-kawaii">
        <img src="assets/images/plushie.png" alt="Logo" />
    </div>

    <div class="right-icons">
        <?php if ($loggedin): ?>
            <span>Selamat datang, <?php echo $username; ?>! (<?php echo ucfirst($user_role); ?>)</span>
            <?php if ($user_role === 'admin'): ?>
                <a href="admin/dashboard.php">Dashboard Admin</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php"><i class="fa-solid fa-user"></i></a>
            <a href="register.php">Sign In</a>
        <?php endif; ?>
    </div>
</div>

<!-- NAVBAR MENU -->
<nav class="navbar">
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="products.php">Shop All</a>
        <a href="new.php">New ✨</a>
        <a href="best_seller.php">Best Seller</a>
        <a href="contact.php">About Us</a>
        
        <a href="cart.php"><i class="fa-solid fa-bag-shopping"></i></a>
    </div>
</nav>


   <section class="hero-banner">
    <div class="hero-content">
        
        <h2 class="hero-title">SPRING SALE</h2>
        <p class="hero-subtitle">UP TO 60% OFF SITEWIDE</p>
        <p class="hero-code">PLUS 25% OFF WITH CODE: <strong>BLOOMING</strong></p>
        <a href="products.php" class="btn-shop">Shop sale</a>
    </div>
</section>


    <div class="main-content">
        <section id="products" class="products-section">
            <h2>Boneka Populer Kami</h2>
            <div class="product-grid">
                <?php
                // Query untuk mengambil produk dari database
                $sql = "SELECT id, name, price, stock, image_url FROM products ORDER BY created_at DESC LIMIT 6"; // Ambil 6 produk terbaru
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    // Output data setiap baris
                    while($row = $result->fetch_assoc()) {
                        // Pastikan ada folder assets/images/products/ dan gambar placeholder.jpg
                        $product_image = !empty($row["image_url"]) && file_exists($row["image_url"]) ? $row["image_url"] : "assets/images/placeholder.jpg"; 
                ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($product_image); ?>" alt="<?php echo htmlspecialchars($row["name"]); ?>">
                        <h3><?php echo htmlspecialchars($row["name"]); ?></h3>
                        <div class="price">Rp <?php echo number_format($row["price"], 0, ',', '.'); ?></div>
                        <div class="stock">Stok: <?php echo $row["stock"]; ?></div>
                        <a href="product_detail.php?id=<?php echo $row['id']; ?>" class="btn-shop">Lihat Detail</a>
                    </div>
                <?php
                    }
                } else {
                    echo "<p>Belum ada produk yang ditambahkan.</p>";
                }
                // Tutup koneksi setelah selesai mengambil data
                $conn->close();
                ?>
            </div>
        </section>
    </div> 
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
        <li><a href="contact.php">About us</a></li>
        <li><a href="terms.php">Terms And Conditions</a></li>
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