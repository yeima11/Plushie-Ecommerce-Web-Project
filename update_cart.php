<?php
session_start();

// Pastikan file koneksi database tersedia. Ini penting untuk validasi stok.
require_once 'db_connect.php';

// Pastikan keranjang belanja ada di sesi. Jika belum, inisialisasi sebagai array kosong.
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = []; // Menggunakan sintaks array pendek (PHP 5.4+)
}

// Hanya proses jika request adalah POST
if ($_SERVER["REQUEST_METHOD"] === "POST") { // Menggunakan '===' untuk perbandingan yang lebih ketat
    // Ambil dan sanitasi input
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $action = isset($_POST['action']) ? trim($_POST['action']) : ''; // Trim whitespace dari action

    // Lanjutkan hanya jika product_id valid (lebih dari 0)
    if ($product_id > 0) {
        // Ambil detail produk dari database untuk validasi stok dan nama
        $sql = "SELECT id, name, stock FROM products WHERE id = ?";
        
        // Persiapkan statement SQL
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $product_id); // Bind parameter integer
            
            // Eksekusi statement
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                
                // Pastikan produk ditemukan di database
                if ($result->num_rows === 1) { // Menggunakan '==='
                    $db_product = $result->fetch_assoc();
                    $product_name = htmlspecialchars($db_product['name']);
                    $available_stock = $db_product['stock'];

                    // Logika berdasarkan aksi yang diminta
                    switch ($action) {
                        case 'update':
                            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

                            if ($quantity > 0) {
                                // Cek apakah produk ada di keranjang untuk update
                                if (isset($_SESSION['cart'][$product_id])) {
                                    // Cek stok yang tersedia sebelum update
                                    if ($quantity > $available_stock) {
                                        $_SESSION['cart'][$product_id]['quantity'] = $available_stock; // Atur ke stok maksimum yang tersedia
                                        $_SESSION['message'] = "Stok untuk " . $product_name . " hanya tersedia " . $available_stock . " unit. Jumlah di keranjang disesuaikan.";
                                        $_SESSION['message_type'] = "warning";
                                    } else {
                                        $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                                        $_SESSION['message'] = "Jumlah " . $product_name . " berhasil diperbarui di keranjang.";
                                        $_SESSION['message_type'] = "success";
                                    }
                                } else {
                                    // Ini kasus jarang, tapi jika produk diupdate padahal tidak di keranjang
                                    // Bisa diarahkan untuk menambahkannya atau memberi pesan info.
                                    // Untuk saat ini, kita bisa arahkan ke halaman produk detail atau index
                                    // atau tambahkan saja jika quantity > 0 dan stok cukup.
                                    // Untuk keranjang, lebih baik produk sudah ada baru di update.
                                    // Di sini, jika belum ada, kita bisa anggap seperti "add to cart"
                                    
                                    // Jika Anda ingin mengizinkan penambahan via update_cart juga:
                                    if ($quantity > $available_stock) {
                                        $quantity_to_add = $available_stock;
                                        $_SESSION['message'] = "Stok untuk " . $product_name . " hanya tersedia " . $available_stock . " unit. Hanya jumlah ini yang ditambahkan.";
                                        $_SESSION['message_type'] = "warning";
                                    } else {
                                        $quantity_to_add = $quantity;
                                        $_SESSION['message'] = $product_name . " berhasil ditambahkan ke keranjang.";
                                        $_SESSION['message_type'] = "success";
                                    }
                                    // Perlu mengambil harga dan image_url lagi untuk menambahkan ke cart jika belum ada
                                    // Atau pastikan update_cart hanya untuk item yang sudah ada.
                                    // Untuk kesederhanaan saat ini, anggap update hanya untuk item yang sudah ada di cart.
                                    // Jika ingin add juga, harus fetch price dan image_url.
                                    // Saat ini, logikanya lebih untuk update/remove.
                                    $_SESSION['message'] = "Produk tidak ditemukan di keranjang untuk update, silakan tambahkan dari halaman produk.";
                                    $_SESSION['message_type'] = "danger";

                                }
                            } else {
                                // Jika quantity 0 atau kurang, hapus item dari keranjang (praktis)
                                if (isset($_SESSION['cart'][$product_id])) {
                                    unset($_SESSION['cart'][$product_id]);
                                    $_SESSION['message'] = $product_name . " berhasil dihapus dari keranjang.";
                                    $_SESSION['message_type'] = "success";
                                } else {
                                    $_SESSION['message'] = "Produk tidak ditemukan di keranjang untuk dihapus.";
                                    $_SESSION['message_type'] = "info"; // Atau danger jika aneh
                                }
                            }
                            break;

                        case 'remove':
                            if (isset($_SESSION['cart'][$product_id])) {
                                unset($_SESSION['cart'][$product_id]);
                                $_SESSION['message'] = $product_name . " berhasil dihapus dari keranjang.";
                                $_SESSION['message_type'] = "success";
                            } else {
                                $_SESSION['message'] = "Produk tidak ditemukan di keranjang untuk dihapus.";
                                $_SESSION['message_type'] = "danger";
                            }
                            break;

                        default:
                            $_SESSION['message'] = "Aksi tidak valid untuk produk ini.";
                            $_SESSION['message_type'] = "danger";
                            break;
                    }
                } else {
                    // Produk tidak ditemukan di database
                    $_SESSION['message'] = "Produk dengan ID #" . $product_id . " tidak ditemukan di database.";
                    $_SESSION['message_type'] = "danger";
                }
            } else {
                // Error saat eksekusi query
                $_SESSION['message'] = "Error saat mengambil detail produk: " . $stmt->error;
                $_SESSION['message_type'] = "danger";
            }
            $stmt->close(); // Tutup statement setelah digunakan
        } else {
            // Error saat mempersiapkan statement
            $_SESSION['message'] = "Error saat mempersiapkan query produk: " . $conn->error;
            $_SESSION['message_type'] = "danger";
        }
    } else {
        // ID produk tidak valid
        $_SESSION['message'] = "ID produk tidak valid untuk diperbarui/dihapus.";
        $_SESSION['message_type'] = "danger";
    }
} else {
    // Metode request tidak valid (bukan POST)
    $_SESSION['message'] = "Metode request tidak valid.";
    $_SESSION['message_type'] = "danger";
}

// Pastikan koneksi ditutup setelah semua operasi database selesai
$conn->close(); 

// Redirect kembali ke halaman keranjang
header("Location: cart.php"); // Menggunakan "Location" dengan L kapital dan spasi setelah titik dua
exit(); // Selalu gunakan exit() setelah header redirect
?>