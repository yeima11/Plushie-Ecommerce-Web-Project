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

$category_name = "";
$error_message = "";
$success_message = "";

// --- Logika Penambahan Kategori (Jika Form Disubmit) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = trim($_POST['name'] ?? '');

    // Validasi input
    if (empty($category_name)) {
        $error_message = "Nama kategori tidak boleh kosong.";
    } else {
        // Cek apakah nama kategori sudah ada
        $sql_check_duplicate = "SELECT id FROM categories WHERE name = ?";
        if ($stmt_check = $conn->prepare($sql_check_duplicate)) {
            $stmt_check->bind_param("s", $category_name);
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
            // --- Insert Data ke Database ---
            $sql_insert = "INSERT INTO categories (name) VALUES (?)";
            if ($stmt = $conn->prepare($sql_insert)) {
                $stmt->bind_param("s", $category_name);
                if ($stmt->execute()) {
                    $success_message = "Kategori '" . htmlspecialchars($category_name) . "' berhasil ditambahkan.";
                    $category_name = ""; // Kosongkan field input setelah sukses
                    
                    // Opsional: Redirect ke manage_categories.php setelah sukses
                    // header("refresh:3; url=manage_categories.php?success=" . urlencode($success_message));
                    // exit;
                } else {
                    $error_message = "Error: Gagal menambahkan kategori. " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_message = "Error: Gagal menyiapkan query penambahan kategori. " . $conn->error;
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
    <title>Tambah Kategori Baru - Admin BonekaKu</title>
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
            <h2>Tambah Kategori Baru</h2>

            <?php if (!empty($success_message)): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="add_category.php" method="POST" class="admin-form">
                <div class="form-group">
                    <label for="name">Nama Kategori:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($category_name); ?>" required>
                </div>

                <button type="submit" class="btn-primary">Tambah Kategori</button>
                <a href="manage_categories.php" class="btn-secondary">Batal</a>
            </form>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> BonekaKu Admin. All rights reserved.</p>
    </footer>
</body>
</html>