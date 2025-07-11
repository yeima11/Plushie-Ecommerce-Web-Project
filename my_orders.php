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

$user_id = $_SESSION['id'];
$orders = [];
$error_message = "";

// Ambil semua pesanan untuk user yang sedang login
$sql_orders = "SELECT id, total_amount, status, order_date, shipping_address 
               FROM orders 
               WHERE user_id = ? 
               ORDER BY order_date DESC"; // Urutkan dari yang terbaru
if ($stmt_orders = $conn->prepare($sql_orders)) {
    $stmt_orders->bind_param("i", $user_id);
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();

    if ($result_orders->num_rows > 0) {
        while ($row = $result_orders->fetch_assoc()) {
            $orders[] = $row;
        }
    } else {
        $error_message = "Anda belum memiliki pesanan apa pun.";
    }
    $stmt_orders->close();
} else {
    $error_message = "Terjadi kesalahan database saat mengambil pesanan: " . $conn->error;
}

$conn->close(); // Tutup koneksi database
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - BonekaKu</title>
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
        <div class="my-orders-container">
            <h2>Pesanan Saya</h2>

            <?php if (!empty($error_message)): ?>
                <div class="alert info"><?php echo $error_message; ?></div>
                <div class="order-actions">
                    <a href="products.php" class="btn-primary">Mulai Berbelanja</a>
                </div>
            <?php elseif (!empty($orders)): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Pesanan</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Alamat Pengiriman</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo date("d M Y H:i", strtotime($order['order_date'])); ?></td>
                                <td>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                                <td><span class="order-status-<?php echo strtolower(htmlspecialchars($order['status'])); ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></td>
                                <td><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></td>
                                <td>
                                    <a href="order_confirmation.php?order_id=<?php echo htmlspecialchars($order['id']); ?>" class="btn-view-details">Lihat Detail</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> BonekaKu. All rights reserved.</p>
    </footer>
</body>
</html>