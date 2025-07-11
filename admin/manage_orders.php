<?php
// BARIS DEBUGGING (opsional, bisa dihapus setelah development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../db_connect.php'; // Pastikan path ini benar

// Cek apakah pengguna sudah login dan memiliki peran 'admin'
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

$orders = [];
$error_message = "";
$success_message = "";

// Daftar status pesanan yang valid
$valid_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];

// --- Logika Update Status Pesanan ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_order_id']) && isset($_POST['new_status'])) {
    $order_id_to_update = filter_var($_POST['update_order_id'], FILTER_SANITIZE_NUMBER_INT);
    $new_status = trim($_POST['new_status']);

    // Validasi status baru
    if (!in_array($new_status, $valid_statuses)) {
        $error_message = "Status tidak valid.";
    } elseif (empty($order_id_to_update) || !is_numeric($order_id_to_update)) {
        $error_message = "ID pesanan tidak valid.";
    } else {
        $sql_update_status = "UPDATE orders SET status = ? WHERE id = ?";
        if ($stmt_update = $conn->prepare($sql_update_status)) {
            $stmt_update->bind_param("si", $new_status, $order_id_to_update);
            if ($stmt_update->execute()) {
                $success_message = "Status pesanan ID " . htmlspecialchars($order_id_to_update) . " berhasil diperbarui menjadi '" . htmlspecialchars($new_status) . "'.";
            } else {
                $error_message = "Error: Gagal memperbarui status pesanan. " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $error_message = "Error: Gagal menyiapkan query update status. " . $conn->error;
        }
    }
}


// --- Ambil Data Pesanan ---
// Join dengan tabel users untuk menampilkan nama pengguna
$sql_orders = "SELECT o.id, u.username, o.order_date, o.total_amount, o.status, o.shipping_address 
               FROM orders o
               JOIN users u ON o.user_id = u.id
               ORDER BY o.order_date DESC"; // Urutkan dari pesanan terbaru

if ($stmt = $conn->prepare($sql_orders)) {
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    } else {
        $error_message = "Tidak ada pesanan yang ditemukan.";
    }
    $stmt->close();
} else {
    $error_message = "Terjadi kesalahan database saat mengambil pesanan: " . $conn->error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesanan - Admin BonekaKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet"></head>
    <style>
        /* CSS Tambahan Khusus untuk manage_orders.php jika diperlukan */
        .status-select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 0.9em;
        }
        .status-form {
            display: inline-block;
        }
        .btn-status-update {
            padding: 5px 10px;
            font-size: 0.9em;
            margin-left: 5px;
        }
    </style>
</head>
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
        <div class="admin-container">
            <h2>Manajemen Pesanan</h2>

            <?php if (!empty($success_message)): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <?php if (empty($orders)): ?>
                <p>Tidak ada pesanan yang ditemukan.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID Pesanan</th>
                                <th>Pelanggan</th>
                                <th>Tanggal Pesanan</th>
                                <th>Jumlah Total</th>
                                <th>Status</th>
                                <th>Alamat Pengiriman</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td><?php echo date('d M Y H:i', strtotime($order['order_date'])); ?></td>
                                    <td>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                                    <td>
                                        <form action="manage_orders.php" method="POST" class="status-form">
                                            <input type="hidden" name="update_order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
                                            <select name="new_status" class="status-select">
                                                <?php foreach ($valid_statuses as $status_option): ?>
                                                    <option value="<?php echo htmlspecialchars($status_option); ?>"
                                                        <?php echo ($order['status'] === $status_option) ? 'selected' : ''; ?>>
                                                        <?php echo ucfirst($status_option); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-action btn-status-update">Update</button>
                                        </form>
                                    </td>
                                    <td><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></td>
                                    <td>
                                        <a href="view_order.php?id=<?php echo htmlspecialchars($order['id']); ?>" class="btn-action view">Detail</a>
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
        <p>&copy; <?php echo date("Y"); ?> BonekaKu Admin. All rights reserved.</p>
    </footer>
</body>
</html>