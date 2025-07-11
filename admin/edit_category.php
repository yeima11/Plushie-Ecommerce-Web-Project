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

$category = null;
$error_message = "";
$success_message = "";

// --- Ambil ID Kategori dari URL ---
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $category_id = filter_var(trim($_GET['id']), FILTER_SANITIZE_NUMBER_INT);
    if (!is_numeric($category_id) || $category_id <= 0) {
        header("location: manage_categories.php?error=" . urlencode("ID kategori tidak valid."));
        exit;
    }

    // --- Ambil Data Kategori yang Akan Diedit ---
    $sql_category = "SELECT id, name FROM categories WHERE id = ?";
    if ($stmt = $conn->prepare($sql_category)) {
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $category = $result->fetch_assoc();
        } else {
            header("location: manage_categories.php?error=" . urlencode("Kategori tidak ditemukan."));
            exit;
        }
        $stmt->close();
    } else {
        $error_message = "Terjadi kesalahan database saat mengambil data kategori: " . $conn->error;
    }
} else {
    header("location: manage_categories.php?error=" . urlencode("ID kategori tidak diberikan."));
    exit;
}

// --- Logika Update Kategori (Jika Form Disubmit) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['category_id'])) {
    $category_id_to_update = filter_var($_POST['category_id'], FILTER_SANITIZE_NUMBER_INT);
    $name = trim($_POST['name'] ?? '');

    // Validasi input
    if (empty($name)) {
        $error_message = "Nama kategori tidak boleh kosong.";
    } else {
        // Cek apakah nama kategori sudah ada (kecuali kategori itu sendiri)
        $sql_check_duplicate = "SELECT id FROM categories WHERE name = ? AND id != ?";
        if ($stmt_check = $conn->prepare($sql_check_duplicate)) {
            $stmt_check->bind_param("si", $name, $category_id_to_update);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $error_message = "Nama kategori ini sudah ada. Mohon gunakan nama lain.";
            }
            $stmt_check->close();
        } else {
            $error_message = "Error: Gagal menyiapkan cek duplikat kategori. " . $conn->error;
        }

        if (empty($error_message)) {
            // --- Update Data di Database ---
            $sql_update = "UPDATE categories SET name = ? WHERE id = ?";
            if ($stmt = $conn->prepare($sql_update)) {
                $stmt->bind_param("si", $name, $category_id_to_update);
                if ($stmt->execute()) {
                    $success_message = "Kategori '" . htmlspecialchars($name) . "' berhasil diperbarui.";
                    // Update objek $category agar form menampilkan data terbaru
                    $category['name'] = $name;
                    
                    // Opsional: Redirect ke manage_categories.php setelah sukses
                    // header("refresh:3; url=manage_categories.php?success=" . urlencode($success_message));
                    // exit;
                } else {
                    $error_message = "Error: Gagal memperbarui kategori. " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_message = "Error: Gagal menyiapkan query pembaruan kategori. " . $conn->error;
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kategori - Admin BonekaKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet"></head>
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
        <div class="admin-container form-container">
            <h2>Edit Kategori: <?php echo htmlspecialchars($category['name'] ?? ''); ?></h2>

            <?php if (!empty($success_message)): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <?php if ($category): ?>
                <form action="edit_category.php?id=<?php echo htmlspecialchars($category['id']); ?>" method="POST" class="admin-form">
                    <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category['id']); ?>">
                    
                    <div class="form-group">
                        <label for="name">Nama Kategori:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                    </div>

                    <button type="submit" class="btn-primary">Perbarui Kategori</button>
                    <a href="manage_categories.php" class="btn-secondary">Batal</a>
                </form>
            <?php else: ?>
                <p>Data kategori tidak dapat dimuat. Silakan kembali ke <a href="manage_categories.php">Manajemen Kategori</a>.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> BonekaKu Admin. All rights reserved.</p>
    </footer>
</body>
</html>