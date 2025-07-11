<?php
// BARIS DEBUGGING INI HARUS DI PALING ATAS FILE
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// echo "DEBUG: PHP script started successfully.<br>"; // Anda bisa menghapus atau mengomentari baris ini jika sudah tidak butuh debugging awal

session_start();
require_once '../db_connect.php'; // Pastikan path ini benar untuk koneksi database

// Cek apakah pengguna sudah login dan memiliki peran 'admin'
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

$products = [];
$error_message = "";
$success_message = "";

// --- Logika Penghapusan Produk ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_product_id'])) {
    $product_id_to_delete = filter_var($_POST['delete_product_id'], FILTER_SANITIZE_NUMBER_INT);

    // Dapatkan image_url sebelum menghapus produk
    $sql_get_image = "SELECT image_url FROM products WHERE id = ?";
    if ($stmt_get_image = $conn->prepare($sql_get_image)) {
        $stmt_get_image->bind_param("i", $product_id_to_delete);
        $stmt_get_image->execute();
        $result_get_image = $stmt_get_image->get_result();
        $product_data = $result_get_image->fetch_assoc();
        $image_to_delete_path = $product_data['image_url'] ?? '';
        $stmt_get_image->close();
    }

    $sql_delete = "DELETE FROM products WHERE id = ?";
    if ($stmt_delete = $conn->prepare($sql_delete)) {
        $stmt_delete->bind_param("i", $product_id_to_delete);
        if ($stmt_delete->execute()) {
            $success_message = "Produk berhasil dihapus.";
            // Hapus file gambar jika ada dan bukan placeholder
            // Pastikan Anda juga memiliki file 'placeholder.jpg' di 'assets/images/' jika digunakan
            if (!empty($image_to_delete_path) && file_exists('../' . $image_to_delete_path) && $image_to_delete_path !== 'assets/images/placeholder.jpg') {
                unlink('../' . $image_to_delete_path); // Hapus file fisik, perhatikan '../' karena relatif dari manage_products.php
            }
        } else {
            $error_message = "Error: Gagal menghapus produk. " . $stmt_delete->error;
        }
        $stmt_delete->close();
    } else {
        $error_message = "Error: Gagal menyiapkan query penghapusan. " . $conn->error;
    }
}

// --- Ambil Data Produk (Setelah Potensi Penghapusan) ---
// Join dengan tabel kategori untuk menampilkan nama kategori
$sql_products = "SELECT p.id, p.name, p.description, p.price, p.stock, p.image_url, c.name AS category_name 
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 ORDER BY p.name ASC";

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

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Admin BonekaKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet"></head>
    <script>
        function confirmDelete(productId, productName) {
            if (confirm("Apakah Anda yakin ingin menghapus produk '" + productName + "'?")) {
                document.getElementById('deleteForm' + productId).submit();
            }
        }
    </script>
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
            <h2>Manajemen Produk</h2>

            <?php if (!empty($success_message)): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="add_product.php" class="btn-primary">Tambah Produk Baru</a>
            </div>

            <?php if (empty($products)): ?>
                <p>Tidak ada produk yang tersedia saat ini.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Gambar</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['id']); ?></td>
                                    <td>
                                        <?php
                                            // Perhatikan path 'assets/images/products/' yang kini relatif dari root proyek
                                            $product_image = !empty($product['image_url']) ? htmlspecialchars('../' . $product['image_url']) : '../assets/images/placeholder.jpg';
                                            // Optional: check if file actually exists on server
                                            if (!file_exists($product_image) && !filter_var($product['image_url'], FILTER_VALIDATE_URL)) {
                                                $product_image = '../assets/images/placeholder.jpg';
                                            }
                                        ?>
                                        <img src="<?php echo $product_image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="table-thumbnail">
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Tidak Berkategori'); ?></td>
                                    <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($product['stock']); ?></td>
                                    <td>
                                        <a href="edit_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn-action edit">Edit</a>
                                        <button class="btn-action delete" onclick="confirmDelete(<?php echo htmlspecialchars($product['id']); ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')">Hapus</button>
                                        
                                        <form id="deleteForm<?php echo htmlspecialchars($product['id']); ?>" action="manage_products.php" method="POST" style="display: none;">
                                            <input type="hidden" name="delete_product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                        </form>
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