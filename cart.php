<?php
session_start();

// Include file koneksi database jika diperlukan untuk informasi tambahan atau validasi
// require_once 'db_connect.php'; 

// Inisialisasi variabel untuk status login dan username
$loggedin = false;
$username = "Tamu"; 
$user_role = "guest"; 

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $loggedin = true;
    $username = htmlspecialchars($_SESSION["username"]);
    $user_role = $_SESSION["role"];
}

// Ambil keranjang belanja dari sesi
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
$total_price = 0; // Inisialisasi total harga

// Ambil pesan notifikasi dari sesi jika ada
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
    // Hapus pesan dari sesi agar tidak muncul lagi setelah refresh
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Toko Boneka</title>
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

    <div class="main-content">
        <div class="cart-container">
            <h2>Keranjang Belanja Kamu</h2>

            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($cart_items)): ?>
                <?php foreach ($cart_items as $product_id => $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <?php 
                                // Path gambar disesuaikan jika perlu, misal 'assets/images/products/' . basename($item["image_url"]);
                                $cart_image = !empty($item["image_url"]) && file_exists($item["image_url"]) ? $item["image_url"] : "assets/images/placeholder.jpg";
                            ?>
                            <img src="<?php echo htmlspecialchars($cart_image); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </div>
                        <div class="cart-item-details">
                            <h3><a href="product_detail.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a></h3>
                            <div class="price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></div>
                        </div>
                        <div class="cart-item-actions">
                            <form action="update_cart.php" method="post" style="display: flex; gap: 5px;">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="99" onchange="this.form.submit()"> 
                                <button type="submit" name="action" value="update" style="display: none;"></button>
                            </form>
                            <form action="update_cart.php" method="post">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <button type="submit" name="action" value="remove" class="btn-remove">Hapus</button>
                            </form>
                        </div>
                    </div>
                    <?php $total_price += $item['price'] * $item['quantity']; ?>
                <?php endforeach; ?>

                <div class="cart-summary">
                    <div class="total"><span>Total:</span> Rp <?php echo number_format($total_price, 0, ',', '.'); ?></div>
                </div>
                <div class="cart-actions">
                    <a href="index.php" class="btn-continue-shopping">Lanjutkan Belanja</a>
                    <a href="checkout.php" class="btn-checkout">Checkout</a> </div>
            <?php else: ?>
                <div class="empty-cart-message">
                    <p>Keranjang belanjamu kosong. Yuk, <a href="index.php">mulai belanja</a> boneka impianmu!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Toko Boneka Impian. Semua Hak Cipta Dilindungi.</p>
    </footer>
</body>
</html>