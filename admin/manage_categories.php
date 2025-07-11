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

$categories = [];
$error_message = "";
$success_message = "";

// --- Logika Penghapusan Kategori ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_category_id'])) {
    $category_id_to_delete = filter_var($_POST['delete_category_id'], FILTER_SANITIZE_NUMBER_INT);

    // Kategori memiliki foreign key di tabel 'products' dengan ON DELETE SET NULL.
    // Jadi, ketika kategori dihapus, product.category_id yang terkait akan otomatis menjadi NULL.
    $sql_delete = "DELETE FROM categories WHERE id = ?";
    if ($stmt_delete = $conn->prepare($sql_delete)) {
        $stmt_delete->bind_param("i", $category_id_to_delete);
        if ($stmt_delete->execute()) {
            $success_message = "Kategori berhasil dihapus. Produk yang terkait kini tidak memiliki kategori.";
        } else {
            $error_message = "Error: Gagal menghapus kategori. " . $stmt_delete->error;
        }
        $stmt_delete->close();
    } else {
        $error_message = "Error: Gagal menyiapkan query penghapusan kategori. " . $conn->error;
    }
}

// --- Ambil Data Kategori (Setelah Potensi Penghapusan) ---
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";

if ($stmt = $conn->prepare($sql_categories)) {
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    } else {
        $error_message = "Tidak ada kategori yang ditemukan.";
    }
    $stmt->close();
} else {
    $error_message = "Terjadi kesalahan database saat mengambil kategori: " . $conn->error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori - Admin BonekaKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet"></head>
    <script>
        function confirmDelete(categoryId, categoryName) {
            if (confirm("Apakah Anda yakin ingin menghapus kategori '" + categoryName + "'? Produk yang terkait akan menjadi 'Tidak Berkategori'.")) {
                document.getElementById('deleteForm' + categoryId).submit();
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
            <h2>Manajemen Kategori</h2>

            <?php if (!empty($success_message)): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="add_category.php" class="btn-primary">Tambah Kategori Baru</a>
            </div>

            <?php if (empty($categories)): ?>
                <p>Tidak ada kategori yang ditemukan.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Kategori</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['id']); ?></td>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td>
                                        <a href="edit_category.php?id=<?php echo htmlspecialchars($category['id']); ?>" class="btn-action edit">Edit</a>
                                        <button class="btn-action delete" onclick="confirmDelete(<?php echo htmlspecialchars($category['id']); ?>, '<?php echo htmlspecialchars(addslashes($category['name'])); ?>')">Hapus</button>
                                        
                                        <form id="deleteForm<?php echo htmlspecialchars($category['id']); ?>" action="manage_categories.php" method="POST" style="display: none;">
                                            <input type="hidden" name="delete_category_id" value="<?php echo htmlspecialchars($category['id']); ?>">
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