<?php
// Pastikan untuk memulai session
session_start();

// Include file koneksi database
require_once 'db_connect.php'; 

// Inisialisasi variabel untuk status login dan username
$loggedin = false;
$username = "Tamu"; 
$user_role = "guest"; 

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $loggedin = true;
    $username = htmlspecialchars($_SESSION["username"]);
    $user_role = $_SESSION["role"];
}

$product = null; // Variabel untuk menyimpan data produk

// Cek apakah ID produk diberikan di URL
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $product_id = trim($_GET['id']);

    // Siapkan SQL SELECT statement
    $sql = "SELECT p.id, p.name, p.description, p.price, p.stock, p.image_url, c.name AS category_name 
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $param_id); // "i" for integer
        $param_id = $product_id;

        if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                // Ambil satu baris hasil
                $product = $result->fetch_assoc();
            } else {
                // Jika ID produk tidak ditemukan
                header("location: index.php"); // Redirect ke halaman utama
                exit();
            }
        } else {
            echo "Oops! Ada yang salah. Silakan coba lagi nanti.";
        }
        $stmt->close();
    }
} else {
    // Jika ID produk tidak diberikan di URL
    header("location: index.php"); // Redirect ke halaman utama
    exit();
}

// Tutup koneksi database setelah selesai mengambil data
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Toko Boneka</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Gaya khusus untuk halaman detail produk */
        .product-detail-container {
            max-width: 900px;
            margin: 60px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-wrap: wrap; /* Untuk responsif */
            gap: 40px; /* Jarak antar kolom */
            align-items: flex-start;
        }

        .product-detail-image {
            flex: 1 1 400px; /* Fleksibel, mulai dari 400px */
            text-align: center;
        }

        .product-detail-image img {
            max-width: 100%;
            height: auto;
            max-height: 450px; /* Batasi tinggi gambar */
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #eee;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .product-detail-info {
            flex: 1 1 400px; /* Fleksibel, mulai dari 400px */
        }

        .product-detail-info h1 {
            font-size: 2.8em;
            color: #333;
            margin-bottom: 10px;
            line-height: 1.2;
        }

        .product-detail-info .category {
            font-size: 1em;
            color: #ff69b4;
            font-weight: bold;
            margin-bottom: 15px;
            display: block;
            text-transform: uppercase;
        }

        .product-detail-info .price {
            font-size: 2.5em;
            color: #f16483;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .product-detail-info .stock {
            font-size: 1.1em;
            color: #555;
            margin-bottom: 25px;
        }
        .product-detail-info .stock.out-of-stock {
            color: #dc3545; /* Merah untuk stok habis */
            font-weight: bold;
        }

        .product-detail-info .description {
            font-size: 1.1em;
            color: #666;
            line-height: 1.7;
            margin-bottom: 30px;
        }

        .add-to-cart-form {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .add-to-cart-form input[type="number"] {
            width: 80px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
            font-size: 1.1em;
        }

        .add-to-cart-form .btn-add-to-cart {
            flex-grow: 1; /* Ambil sisa ruang */
            max-width: 250px; /* Batasi lebar tombol */
            padding: 12px 25px;
            font-size: 1.1em;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .product-detail-container {
                flex-direction: column;
                gap: 20px;
                padding: 20px;
                margin: 30px auto;
            }
            .product-detail-image,
            .product-detail-info {
                flex: 1 1 100%; /* Ambil lebar penuh */
            }
            .product-detail-info h1 {
                font-size: 2em;
                text-align: center;
            }
            .product-detail-info .category,
            .product-detail-info .price,
            .product-detail-info .stock,
            .product-detail-info .description {
                text-align: center;
            }
            .add-to-cart-form {
                flex-direction: column;
                width: 100%;
            }
            .add-to-cart-form input[type="number"] {
                width: 100%;
                max-width: 150px; /* Batasi lebar input number */
            }
            .add-to-cart-form .btn-add-to-cart {
                width: 100%;
                max-width: none; /* Izinkan lebar penuh */
            }
            .quantity-control {
    display: flex;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 5px;
    overflow: hidden;
    width: fit-content;
}

.qty-btn {
    background-color: #f16483;
    color: #fff;
    border: none;
    padding: 10px 15px;
    cursor: pointer;
    font-size: 1.2em;
    font-weight: bold;
    transition: background-color 0.2s;
}

.qty-btn:hover {
    background-color: #d94b6b;
}

.quantity-control input[type="number"] {
    width: 60px;
    padding: 10px;
    border: none;
    text-align: center;
    font-size: 1.1em;
}
        }
    </style>
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
        <?php if ($product): ?>
            <div class="product-detail-container">
                <div class="product-detail-image">
                    <?php
                        $detail_image = !empty($product["image_url"]) && file_exists($product["image_url"]) ? $product["image_url"] : "assets/images/placeholder.jpg";
                    ?>
                    <img src="<?php echo htmlspecialchars($detail_image); ?>" alt="<?php echo htmlspecialchars($product["name"]); ?>">
                </div>
                <div class="product-detail-info">
                    <span class="category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    <div class="price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></div>
                    <div class="stock <?php echo ($product['stock'] == 0) ? 'out-of-stock' : ''; ?>">
                        Stok: <?php echo $product['stock'] > 0 ? $product['stock'] . ' unit' : 'Habis'; ?>
                    </div>
                    <p class="description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    
                    <form action="add_to_cart.php" method="post" class="add-to-cart-form">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="number" name="quantity" value="1" min="1" <?php echo ($product['stock'] == 0) ? 'disabled' : 'max="' . $product['stock'] . '"'; ?>>
                        <button type="submit" class="btn-add-to-cart" <?php echo ($product['stock'] == 0) ? 'disabled' : ''; ?>>
                            <?php echo ($product['stock'] == 0) ? 'Stok Habis' : 'Tambah ke Keranjang'; ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 50px;">
                <p>Produk tidak ditemukan.</p>
                <a href="index.php" class="btn-primary" style="max-width: 200px; margin: 20px auto;">Kembali ke Beranda</a>
            </div>
        <?php endif; ?>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const minusBtn = document.querySelector('.qty-btn.minus');
    const plusBtn = document.querySelector('.qty-btn.plus');
    const quantityInput = document.getElementById('quantity-input');

    const min = parseInt(quantityInput.min);
    const max = parseInt(quantityInput.max);

    minusBtn.addEventListener('click', () => {
        let current = parseInt(quantityInput.value);
        if (current > min) {
            quantityInput.value = current - 1;
        }
    });

    plusBtn.addEventListener('click', () => {
        let current = parseInt(quantityInput.value);
        if (current < max) {
            quantityInput.value = current + 1;
        }
    });
});
</script>

</body>
</html>