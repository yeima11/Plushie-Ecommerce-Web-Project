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

$product = null;
$categories = [];
$error_message = "";
$success_message = "";

// --- Ambil ID Produk dari URL ---
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $product_id = filter_var(trim($_GET['id']), FILTER_SANITIZE_NUMBER_INT);
    if (!is_numeric($product_id) || $product_id <= 0) {
        header("location: manage_products.php?error=" . urlencode("ID produk tidak valid."));
        exit;
    }

    // --- Ambil Data Produk yang Akan Diedit ---
    $sql_product = "SELECT id, name, description, price, stock, image_url, category_id FROM products WHERE id = ?";
    if ($stmt = $conn->prepare($sql_product)) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $product = $result->fetch_assoc();
        } else {
            header("location: manage_products.php?error=" . urlencode("Produk tidak ditemukan."));
            exit;
        }
        $stmt->close();
    } else {
        $error_message = "Terjadi kesalahan database saat mengambil data produk: " . $conn->error;
    }
} else {
    header("location: manage_products.php?error=" . urlencode("ID produk tidak diberikan."));
    exit;
}

// --- Ambil Kategori untuk Dropdown ---
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
if ($stmt = $conn->prepare($sql_categories)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    $stmt->close();
} else {
    $error_message = "Gagal mengambil kategori: " . $conn->error;
}


// --- Logika Update Produk (Jika Form Disubmit) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id'])) {
    $product_id = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? '', FILTER_VALIDATE_INT);
    $category_id = filter_var($_POST['category_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);

    $new_image_url = $product['image_url']; // Default: gunakan gambar lama

    // Validasi input
    if (empty($name) || empty($description) || $price === false || $stock === false || empty($category_id)) {
        $error_message = "Semua kolom (Nama, Deskripsi, Harga, Stok, Kategori) harus diisi dengan benar.";
    } elseif ($price <= 0) {
        $error_message = "Harga harus lebih besar dari nol.";
    } elseif ($stock < 0) {
        $error_message = "Stok tidak bisa kurang dari nol.";
    } else {
        // --- Penanganan Upload Gambar Baru (Jika ada) ---
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../assets/images/products/"; // Folder tujuan upload. Pastikan folder ini ada!
            
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $new_file_name = uniqid('product_') . '.' . $file_extension; // Nama file unik
            $target_file = $target_dir . $new_file_name;
            $upload_ok = true;
            $image_file_type = strtolower($file_extension);

            // Periksa apakah file gambar asli atau palsu
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if($check === false) {
                $error_message = "File bukan gambar.";
                $upload_ok = false;
            }

            // Periksa ukuran file (maks 5MB)
            if ($_FILES["image"]["size"] > 5000000) {
                $error_message = "Ukuran file terlalu besar, maksimal 5MB.";
                $upload_ok = false;
            }

            // Izinkan format file tertentu
            if($image_file_type != "jpg" && $image_file_type != "png" && $image_file_type != "jpeg" && $image_file_type != "gif" ) {
                $error_message = "Maaf, hanya file JPG, JPEG, PNG & GIF yang diperbolehkan.";
                $upload_ok = false;
            }

            // Jika semua cek berhasil, coba upload file
            if ($upload_ok) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $new_image_url = 'assets/images/products/' . $new_file_name; // Path relatif untuk disimpan di DB
                    
                    // Hapus gambar lama jika ada dan bukan placeholder
                    if (!empty($product['image_url']) && file_exists('../' . $product['image_url']) && $product['image_url'] !== 'assets/images/placeholder.jpg') {
                        unlink('../' . $product['image_url']);
                    }
                } else {
                    $error_message = "Maaf, terjadi kesalahan saat mengunggah file gambar baru Anda.";
                }
            }
        }
        
        // Hanya lanjutkan jika tidak ada error dari upload atau validasi lainnya
        if (empty($error_message)) {
            // --- Update Data di Database ---
            $sql_update = "UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image_url = ?, category_id = ? WHERE id = ?";
            if ($stmt = $conn->prepare($sql_update)) {
                $stmt->bind_param("ssdissi", $name, $description, $price, $stock, $new_image_url, $category_id, $product_id);
                if ($stmt->execute()) {
                    $success_message = "Produk '" . htmlspecialchars($name) . "' berhasil diperbarui.";
                    // Update objek $product agar form menampilkan data terbaru
                    $product['name'] = $name;
                    $product['description'] = $description;
                    $product['price'] = $price;
                    $product['stock'] = $stock;
                    $product['image_url'] = $new_image_url;
                    $product['category_id'] = $category_id;
                    
                    // Opsional: Redirect ke manage_products.php setelah sukses
                    // header("refresh:3; url=manage_products.php?success=" . urlencode($success_message));
                    // exit;
                } else {
                    $error_message = "Error: Gagal memperbarui produk. " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_message = "Error: Gagal menyiapkan query pembaruan produk. " . $conn->error;
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
    <title>Edit Produk - Admin BonekaKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet"></head>
</head>
<body>
    <header>
        <div class="header-top">
            <span>Selamat datang, Admin <?php echo htmlspecialchars($_SESSION["username"]); ?>!</span>
            <a href="../logout.php">Logout</a>
        </div>
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
            <h2>Edit Produk: <?php echo htmlspecialchars($product['name'] ?? ''); ?></h2>

            <?php if (!empty($success_message)): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <?php if ($product): ?>
                <form action="edit_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" method="POST" enctype="multipart/form-data" class="admin-form">
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                    
                    <div class="form-group">
                        <label for="name">Nama Produk:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Deskripsi:</label>
                        <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="price">Harga (Rp):</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="stock">Stok:</label>
                        <input type="number" id="stock" name="stock" min="0" value="<?php echo htmlspecialchars($product['stock']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Kategori:</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>" 
                                    <?php echo ($category['id'] == $product['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Gambar Saat Ini:</label><br>
                        <?php 
                            $current_image = !empty($product['image_url']) ? htmlspecialchars('../' . $product['image_url']) : '../assets/images/placeholder.jpg';
                            // Optional: check if file actually exists on server
                            if (!file_exists($current_image) && !filter_var($product['image_url'], FILTER_VALIDATE_URL)) {
                                $current_image = '../assets/images/placeholder.jpg';
                            }
                        ?>
                        <img src="<?php echo $current_image; ?>" alt="Current Image" style="max-width: 150px; height: auto; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px;">
                    </div>

                    <div class="form-group">
                        <label for="image">Ganti Gambar Produk (opsional):</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <small>Maksimal 5MB. Format: JPG, JPEG, PNG, GIF. Biarkan kosong jika tidak ingin mengganti gambar.</small>
                    </div>

                    <button type="submit" class="btn-primary">Perbarui Produk</button>
                    <a href="manage_products.php" class="btn-secondary">Batal</a>
                </form>
            <?php else: ?>
                <p>Data produk tidak dapat dimuat. Silakan kembali ke <a href="manage_products.php">Manajemen Produk</a>.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> BonekaKu Admin. All rights reserved.</p>
    </footer>
</body>
</html>