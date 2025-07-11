<?php
session_start();
require_once 'db_connect.php';

// Cek apakah pengguna login
$loggedin = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$username = $loggedin ? $_SESSION['username'] : '';
$user_role = $loggedin ? $_SESSION['role'] : '';

require_once 'db_connect.php';

// Ambil produk dari kategori Best Seller (misalnya category_id = 3)
$sql = "SELECT id, name, price, stock, image_url FROM products WHERE category_id = 20 ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Produk Best Seller</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header><div class="header-top">

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
</header>

    <main class="main-content">
        <section class="products-section full-page">
            <h2>Produk Best Seller</h2>
            <div class="product-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="product-card">
                            <img src="<?php echo htmlspecialchars($row["image_url"]); ?>" alt="<?php echo htmlspecialchars($row["name"]); ?>">
                            <h3><?php echo htmlspecialchars($row["name"]); ?></h3>
                            <div class="price">Rp <?php echo number_format($row["price"], 0, ',', '.'); ?></div>
                            <div class="stock">Stok: <?php echo $row["stock"]; ?></div>
                            <a href="product_detail.php?id=<?php echo $row['id']; ?>" class="btn-shop">Lihat Detail</a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Belum ada produk best seller.</p>
                <?php endif; ?>
            </div>
        </section>
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
