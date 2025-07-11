<?php
session_start();
$loggedin = false;
$username = "Tamu";
$user_role = "guest";

// Redirect jika belum login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php?redirect=checkout.php");
    exit;
}

// Include file koneksi database untuk mengambil data user dan validasi stok terbaru
require_once 'db_connect.php'; 

// Inisialisasi variabel user
$user_id = $_SESSION["id"];
$username = htmlspecialchars($_SESSION["username"]);
$user_role = $_SESSION["role"];

// Ambil keranjang belanja dari sesi
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : []; // Menggunakan sintaks array pendek

// Redirect jika keranjang kosong setelah validasi atau memang belum ada item
if (empty($cart_items)) {
    $_SESSION['message'] = "Keranjang belanja Anda kosong. Silakan tambahkan produk sebelum checkout.";
    $_SESSION['message_type'] = "warning";
    header("location: cart.php");
    exit;
}

$total_price = 0; // Inisialisasi total harga

// Ambil data email user dari database (hanya email yang tersedia di tabel users Anda)
$email = '';
$sql_user_data = "SELECT email FROM users WHERE id = ?";
if ($stmt_user = $conn->prepare($sql_user_data)) {
    $stmt_user->bind_param("i", $user_id);
    if ($stmt_user->execute()) {
        $result_user = $stmt_user->get_result();
        if ($result_user->num_rows == 1) {
            $user_data = $result_user->fetch_assoc();
            $email = htmlspecialchars($user_data['email'] ?? '');
        }
    }
    $stmt_user->close();
}

// Inisialisasi variabel formulir lainnya (tidak bisa diisi otomatis dari DB users saat ini)
$full_name = '';
$phone_number = '';
$address = '';
$city = '';
$postal_code = '';
$country = 'Indonesia'; // Defaultkan ke Indonesia

// Ambil pesan notifikasi dari sesi jika ada
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Validasi stok ulang sebelum checkout ditampilkan
$stock_issues = false;
$updated_cart_items = []; 
foreach ($cart_items as $product_id => $item) {
    $sql_stock = "SELECT name, price, stock, image_url FROM products WHERE id = ?";
    if ($stmt_stock = $conn->prepare($sql_stock)) {
        $stmt_stock->bind_param("i", $product_id);
        if ($stmt_stock->execute()) {
            $result_stock = $stmt_stock->get_result();
            if ($result_stock->num_rows == 1) {
                $db_product = $result_stock->fetch_assoc();
                $item_name = htmlspecialchars($db_product['name']);
                $current_stock = $db_product['stock'];
                $item_price = $db_product['price'];

                if ($item['quantity'] > $current_stock) {
                    $stock_issues = true;
                    $item['quantity'] = $current_stock;
                    $_SESSION['cart'][$product_id]['quantity'] = $current_stock; 
                    
                    if ($current_stock == 0) {
                        $_SESSION['message'] = "Stok untuk '" . $item_name . "' saat ini habis. Produk dihapus dari keranjang.";
                        $_SESSION['message_type'] = "danger";
                        unset($_SESSION['cart'][$product_id]);
                        continue; 
                    } else {
                        $_SESSION['message'] = "Stok untuk '" . $item_name . "' tidak mencukupi. Jumlah disesuaikan ke " . $current_stock . ".";
                        $_SESSION['message_type'] = "warning";
                    }
                }
                $item['price'] = $item_price;
                $item['image_url'] = $db_product['image_url']; 
                $updated_cart_items[$product_id] = $item; 
                $total_price += $item_price * $item['quantity'];
            } else {
                $stock_issues = true;
                $_SESSION['message'] = "Produk dengan ID " . $product_id . " tidak ditemukan di database dan telah dihapus dari keranjang.";
                $_SESSION['message_type'] = "danger";
                unset($_SESSION['cart'][$product_id]);
            }
        } else {
            $_SESSION['message'] = "Error saat memeriksa stok produk: " . $stmt_stock->error;
            $_SESSION['message_type'] = "danger";
        }
        $stmt_stock->close();
    }
}
$_SESSION['cart'] = $updated_cart_items; 

if (empty($_SESSION['cart'])) {
    header("location: cart.php");
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Toko Boneka</title>
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
        <div class="checkout-container">
            <h2>Langkah Terakhir: Checkout Pesanan</h2>

            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="order-summary">
                <h3>Ringkasan Pesanan</h3>
                <?php if (!empty($updated_cart_items)): ?>
                    <?php foreach ($updated_cart_items as $product_id => $item): ?>
                        <div class="summary-item">
                            <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="item-qty">x<?php echo $item['quantity']; ?></span>
                            <span class="item-price">Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-total">
                        <span>Total Keseluruhan:</span>
                        <span>Rp <?php echo number_format($total_price, 0, ',', '.'); ?></span>
                    </div>
                <?php else: ?>
                    <p class="empty-cart-message">Keranjang Anda kosong setelah validasi. Silakan kembali ke <a href="cart.php">keranjang</a>.</p>
                <?php endif; ?>
            </div>

            <div class="shipping-payment-form">
                <h3>Detail Pengiriman & Pembayaran</h3>
                <form action="process_checkout.php" method="post">
                    <h4>Informasi Pengiriman</h4>
                    <div class="form-group">
                        <label for="full_name">Nama Lengkap:</label>
                        <input type="text" id="full_name" name="full_name" value="" placeholder="Masukkan nama lengkap Anda" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Nomor Telepon:</label>
                        <input type="tel" id="phone_number" name="phone_number" value="" placeholder="Cth: 081234567890" required>
                    </div>
                    <div class="form-group">
                        <label for="address">Alamat Lengkap:</label>
                        <textarea id="address" name="address" rows="4" placeholder="Jl. Contoh No. 123, RT 01 RW 02, Desa/Kelurahan..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="city">Kota/Kabupaten:</label>
                        <input type="text" id="city" name="city" value="" placeholder="Cth: Jakarta Pusat" required>
                    </div>
                    <div class="form-group">
                        <label for="postal_code">Kode Pos:</label>
                        <input type="text" id="postal_code" name="postal_code" value="" placeholder="Cth: 10110" required>
                    </div>
                    <div class="form-group">
                        <label for="country">Negara:</label>
                        <select id="country" name="country" required>
                            <option value="Indonesia" selected>Indonesia</option>
                            </select>
                    </div>

                    <div class="payment-methods">
                        <h4>Metode Pembayaran</h4>
                        <div class="form-group">
                            <label>
                                <input type="radio" name="payment_method" value="Bank Transfer" required> Transfer Bank
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="radio" name="payment_method" value="Cash On Delivery (COD)" required> Cash On Delivery (COD)
                            </label>
                        </div>
                        </div>

                    <button type="submit" class="btn-place-order">Buat Pesanan Sekarang</button>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Toko Boneka Impian. Semua Hak Cipta Dilindungi.</p>
    </footer>
</body>
</html>