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

$users = [];
$error_message = "";
$success_message = "";

// --- Logika Penghapusan Pengguna ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user_id'])) {
    $user_id_to_delete = filter_var($_POST['delete_user_id'], FILTER_SANITIZE_NUMBER_INT);

    // Pencegahan: Admin tidak bisa menghapus akunnya sendiri
    if ($user_id_to_delete == $_SESSION['id']) {
        $error_message = "Anda tidak bisa menghapus akun Anda sendiri.";
    } else {
        // Query untuk menghapus pengguna
        // Karena ada FOREIGN KEY ON DELETE CASCADE di tabel orders,
        // semua pesanan yang terkait dengan user ini juga akan terhapus otomatis.
        $sql_delete = "DELETE FROM users WHERE id = ?";
        if ($stmt_delete = $conn->prepare($sql_delete)) {
            $stmt_delete->bind_param("i", $user_id_to_delete);
            if ($stmt_delete->execute()) {
                $success_message = "Pengguna ID " . htmlspecialchars($user_id_to_delete) . " berhasil dihapus.";
            } else {
                $error_message = "Error: Gagal menghapus pengguna. " . $stmt_delete->error;
            }
            $stmt_delete->close();
        } else {
            $error_message = "Error: Gagal menyiapkan query penghapusan pengguna. " . $conn->error;
        }
    }
}

// --- Ambil Data Pengguna ---
// Tidak mengambil password di sini untuk keamanan.
$sql_users = "SELECT id, username, email, role, created_at FROM users ORDER BY username ASC";

if ($stmt = $conn->prepare($sql_users)) {
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    } else {
        $error_message = "Tidak ada pengguna yang ditemukan.";
    }
    $stmt->close();
} else {
    $error_message = "Terjadi kesalahan database saat mengambil pengguna: " . $conn->error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Admin BonekaKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet"></head>
    <script>
        function confirmDelete(userId, username) {
            // Cek apakah pengguna mencoba menghapus dirinya sendiri (dapatkan ID pengguna dari sesi PHP)
            const currentAdminId = <?php echo json_encode($_SESSION['id']); ?>;
            if (userId === currentAdminId) {
                alert("Anda tidak bisa menghapus akun Anda sendiri.");
                return false;
            }

            if (confirm("Apakah Anda yakin ingin menghapus pengguna '" + username + "'? Semua pesanan yang terkait juga akan dihapus.")) {
                document.getElementById('deleteForm' + userId).submit();
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
            <h2>Manajemen Pengguna</h2>

            <?php if (!empty($success_message)): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="action-buttons">
                </div>

            <?php if (empty($users)): ?>
                <p>Tidak ada pengguna yang ditemukan.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Peran</th>
                                <th>Terdaftar Sejak</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                    <td><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-action delete" 
                                                onclick="confirmDelete(<?php echo htmlspecialchars($user['id']); ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>')">
                                            Hapus
                                        </button>
                                        
                                        <form id="deleteForm<?php echo htmlspecialchars($user['id']); ?>" action="manage_users.php" method="POST" style="display: none;">
                                            <input type="hidden" name="delete_user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
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