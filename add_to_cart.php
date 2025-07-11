<?php
session_start();

// Include file koneksi database untuk mendapatkan detail produk
require_once 'db_connect.php';

// Pastikan keranjang belanja ada di sesi. Jika belum, inisialisasi sebagai array kosong.
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Cek apakah ada data yang dikirim melalui POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data product_id dan quantity
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    // Pastikan product_id valid (lebih dari 0) dan quantity valid (lebih dari 0)
    if ($product_id > 0 && $quantity > 0) {
        // Ambil detail produk dari database untuk memastikan validitas dan harga/stok
        $sql = "SELECT id, name, price, stock, image_url FROM products WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $product_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows == 1) {
                    $product = $result->fetch_assoc();

                    // Cek ketersediaan stok
                    if ($product['stock'] == 0) {
                        $_SESSION['message'] = "Maaf, stok " . htmlspecialchars($product['name']) . " saat ini habis.";
                        $_SESSION['message_type'] = "danger";
                        header("location: product_detail.php?id=" . $product_id);
                        exit();
                    }

                    // Logika penambahan/update produk di keranjang
                    if (isset($_SESSION['cart'][$product_id])) {
                        // Produk sudah ada di keranjang, update jumlahnya
                        $current_quantity = $_SESSION['cart'][$product_id]['quantity'];
                        $new_quantity = $current_quantity + $quantity;

                        // Pastikan tidak melebihi stok yang tersedia
                        if ($new_quantity > $product['stock']) {
                            $_SESSION['message'] = "Maaf, Anda hanya bisa menambahkan maksimal " . $product['stock'] . " unit " . htmlspecialchars($product['name']) . " ke keranjang.";
                            $_SESSION['message_type'] = "warning";
                            $_SESSION['cart'][$product_id]['quantity'] = $product['stock']; // Set ke jumlah maksimum yang tersedia
                        } else {
                            $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
                            $_SESSION['message'] = htmlspecialchars($product['name']) . " berhasil ditambahkan ke keranjang.";
                            $_SESSION['message_type'] = "success";
                        }
                    } else {
                        // Produk belum ada di keranjang, tambahkan baru
                        // Pastikan quantity yang ditambahkan tidak melebihi stok
                        if ($quantity > $product['stock']) {
                             $_SESSION['message'] = "Maaf, Anda hanya bisa menambahkan maksimal " . $product['stock'] . " unit " . htmlspecialchars($product['name']) . " ke keranjang.";
                            $_SESSION['message_type'] = "warning";
                            $quantity_to_add = $product['stock']; // Tambahkan hanya yang tersedia
                        } else {
                            $quantity_to_add = $quantity;
                            $_SESSION['message'] = htmlspecialchars($product['name']) . " berhasil ditambahkan ke keranjang.";
                            $_SESSION['message_type'] = "success";
                        }

                        $_SESSION['cart'][$product_id] = array(
                            'id' => $product['id'],
                            'name' => $product['name'],
                            'price' => $product['price'],
                            'quantity' => $quantity_to_add,
                            'image_url' => $product['image_url']
                        );
                    }
                } else {
                    $_SESSION['message'] = "Produk tidak ditemukan.";
                    $_SESSION['message_type'] = "danger";
                }
            } else {
                $_SESSION['message'] = "Error saat mengambil detail produk.";
                $_SESSION['message_type'] = "danger";
            }
            $stmt->close();
        }
    } else {
        $_SESSION['message'] = "ID produk atau jumlah tidak valid.";
        $_SESSION['message_type'] = "danger";
    }
} else {
    $_SESSION['message'] = "Metode request tidak valid.";
    $_SESSION['message_type'] = "danger";
}

$conn->close();

// Redirect kembali ke halaman produk detail atau ke halaman keranjang
// Untuk sementara, kita redirect ke halaman keranjang
header("location: cart.php"); // Kita akan membuat cart.php selanjutnya
exit();
?>