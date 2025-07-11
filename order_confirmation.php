<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connect.php'; // Pastikan path ini benar

// Periksa apakah user sudah login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Redirect ke halaman login jika belum login
    header("location: login.php");
    exit;
}

$order_id = null;
$order_details = null;
$order_items = [];
$error_message = "";

// Pastikan order_id ada di URL
if (isset($_GET['order_id']) && !empty(trim($_GET['order_id']))) {
    $order_id = filter_var(trim($_GET['order_id']), FILTER_VALIDATE_INT); // Sanitasi input

    if ($order_id === false || $order_id <= 0) {
        $error_message = "ID pesanan tidak valid.";
    } else {
        // Ambil detail pesanan utama
        // MENGUBAH 'created_at' MENJADI 'order_date' SESUAI SKEMA DATABASE ANDA
        $sql_order = "SELECT id, user_id, total_amount, status, shipping_address, order_date FROM orders WHERE id = ? AND user_id = ?";
        if ($stmt_order = $conn->prepare($sql_order)) {
            $stmt_order->bind_param("ii", $order_id, $_SESSION['id']);
            $stmt_order->execute();
            $result_order = $stmt_order->get_result();

            if ($result_order->num_rows === 1) {
                $order_details = $result_order->fetch_assoc();

                // Ambil detail item pesanan
                $sql_items = "SELECT oi.quantity, oi.price_at_order, p.name AS product_name
                              FROM order_items oi
                              JOIN products p ON oi.product_id = p.id
                              WHERE oi.order_id = ?";
                if ($stmt_items = $conn->prepare($sql_items)) {
                    $stmt_items->bind_param("i", $order_id);
                    $stmt_items->execute();
                    $result_items = $stmt_items->get_result();
                    while ($row = $result_items->fetch_assoc()) {
                        $order_items[] = $row;
                    }
                    $stmt_items->close();
                } else {
                    $error_message = "Terjadi kesalahan saat mengambil detail item pesanan: " . $conn->error;
                }

            } else {
                $error_message = "Pesanan tidak ditemukan atau Anda tidak memiliki akses ke pesanan ini.";
            }
            $stmt_order->close();
        } else {
            $error_message = "Terjadi kesalahan database saat mengambil pesanan: " . $conn->error;
        }
    }
} else {
    $error_message = "ID pesanan tidak ditemukan di URL.";
}

$conn->close(); // Tutup koneksi database
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pesanan - BonekaKu</title>
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

    <main class="main-content">
        <div class="order-confirmation-container">
            <?php if (!empty($error_message)): ?>
                <div class="alert danger"><?php echo $error_message; ?></div>
            <?php elseif ($order_details): ?>
                <h2>Pesanan Berhasil Dibuat!</h2>
                <div class="alert success">Terima kasih atas pesanan Anda. Detail pesanan Anda di bawah ini:</div>

                <div class="order-summary-box">
                    <h3>Detail Pesanan #<?php echo htmlspecialchars($order_details['id']); ?></h3>
                    <p><strong>Tanggal Pesanan:</strong> <?php echo date("d M Y H:i", strtotime($order_details['order_date'])); ?></p>
                    <p><strong>Status:</strong> <span class="order-status-<?php echo strtolower(htmlspecialchars($order_details['status'])); ?>"><?php echo htmlspecialchars(ucfirst($order_details['status'])); ?></span></p>
                    <p><strong>Total Pembayaran:</strong> Rp <?php echo number_format($order_details['total_amount'], 0, ',', '.'); ?></p>
                    <p><strong>Alamat Pengiriman:</strong> <?php echo nl2br(htmlspecialchars($order_details['shipping_address'])); ?></p>
                </div>

                <div class="order-items-detail">
                    <h3>Item Pesanan</h3>
                    <?php if (!empty($order_items)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Kuantitas</th>
                                    <th>Harga Satuan</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td>Rp <?php echo number_format($item['price_at_order'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($item['quantity'] * $item['price_at_order'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                                    <td><strong>Rp <?php echo number_format($order_details['total_amount'], 0, ',', '.'); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    <?php else: ?>
                        <p>Tidak ada item yang ditemukan untuk pesanan ini.</p>
                    <?php endif; ?>
                </div>

                <div class="order-confirmation-actions">
                    <a href="products.php" class="btn-primary">Lanjutkan Belanja</a>
                    <a href="my_orders.php" class="btn-secondary">Lihat Pesanan Saya</a>
                </div>

            <?php else: ?>
                <div class="alert danger">Gagal memuat detail pesanan. Mohon coba lagi atau hubungi dukungan.</div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> BonekaKu. All rights reserved.</p>
    </footer>
</body>
</html>