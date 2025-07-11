<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connect.php'; // Pastikan path ini benar

$loggedin = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';

$products = [];
$error_message = "";

// Ambil semua produk dari database
// Anda bisa menambahkan filter, paginasi, atau sorting di sini jika diperlukan di masa mendatang
$sql_products = "SELECT id, name, description, price, stock, image_url, category_id FROM products ORDER BY name ASC";
if ($stmt = $conn->prepare($sql_products)) {
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    } else {
        $error_message = "Tidak ada produk yang ditemukan.";
    }
    $stmt->close();
} else {
    $error_message = "Terjadi kesalahan database saat mengambil produk: " . $conn->error;
}

$conn->close(); // Tutup koneksi database
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk Kami - BonekaKu</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>
    <header>
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
    </header>
    <h2>Semua Produk</h2>
<main class="product-grid">
    
<?php foreach ($products as $product): ?>
    <div class="product-card">
        <a href="product_detail.php?id=<?php echo htmlspecialchars($product['id']); ?>">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </a>
        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
        <p class="price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>

         <a href="product_detail.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn-shop">Shop</a>
    </div>
<?php endforeach; ?>
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
</html>