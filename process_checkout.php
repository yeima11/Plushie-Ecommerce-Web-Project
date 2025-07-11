<?php
session_start();

// Pastikan user sudah login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php?redirect=checkout.php");
    exit;
}

// Pastikan request datang dari metode POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['message'] = "Metode request tidak valid.";
    $_SESSION['message_type'] = "danger";
    header("location: checkout.php");
    exit;
}

// Include file koneksi database
require_once 'db_connect.php';

$user_id = $_SESSION["id"];
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Redirect jika keranjang kosong (mungkin karena sudah divalidasi di checkout.php)
if (empty($cart_items)) {
    $_SESSION['message'] = "Keranjang belanja Anda kosong. Tidak ada yang bisa diproses.";
    $_SESSION['message_type'] = "warning";
    $conn->close(); // Pastikan koneksi ditutup sebelum keluar
    header("location: cart.php");
    exit;
}

// 1. Ambil dan sanitasi data dari form
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');
$country = trim($_POST['country'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');

// Gabungkan detail alamat menjadi satu string untuk kolom `shipping_address` di tabel `orders` Anda
$shipping_address_combined = $full_name . ", "
                            . $phone_number . ", "
                            . $address . ", "
                            . $city . ", "
                            . $postal_code . ", "
                            . $country;


// Validasi dasar input form
if (empty($full_name) || empty($email) || empty($phone_number) || empty($address) || empty($city) || empty($postal_code) || empty($payment_method)) {
    $_SESSION['message'] = "Harap lengkapi semua informasi pengiriman dan metode pembayaran.";
    $_SESSION['message_type'] = "danger";
    $conn->close(); // Tutup koneksi sebelum redirect
    header("location: checkout.php");
    exit;
}

// Validasi email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['message'] = "Format email tidak valid.";
    $_SESSION['message_type'] = "danger";
    $conn->close(); // Tutup koneksi sebelum redirect
    header("location: checkout.php");
    exit;
}

// Inisialisasi total harga dan array untuk menyimpan detail item yang akan dipesan
$total_amount = 0;
$order_products = [];
$stock_issues_found = false; // Flag untuk melacak masalah stok

// Dimulai transaksi database untuk memastikan atomisitas (semua berhasil atau semua gagal)
$conn->begin_transaction();

try {
    foreach ($cart_items as $product_id => $item) {
        $sql_product = "SELECT name, price, stock FROM products WHERE id = ?";
        if ($stmt_product = $conn->prepare($sql_product)) {
            $stmt_product->bind_param("i", $product_id);
            $stmt_product->execute();
            $result_product = $stmt_product->get_result();

            if ($result_product->num_rows === 1) {
                $db_product = $result_product->fetch_assoc();
                $item_name = htmlspecialchars($db_product['name']);
                $current_stock = $db_product['stock'];
                $item_price = $db_product['price'];

                // Cek ketersediaan stok
                if ($item['quantity'] > $current_stock) {
                    $stock_issues_found = true;
                    // Rollback transaksi jika ada masalah stok
                    $conn->rollback();
                    $_SESSION['message'] = "Stok untuk '" . $item_name . "' tidak mencukupi. Hanya tersedia " . $current_stock . " unit. Silakan sesuaikan keranjang Anda.";
                    $_SESSION['message_type'] = "danger";
                    $stmt_product->close(); // Tutup statement sebelum koneksi ditutup
                    $conn->close(); // Tutup koneksi sebelum redirect
                    header("location: checkout.php");
                    exit;
                }

                // Tambahkan ke daftar produk pesanan dengan harga dan kuantitas valid
                $order_products[] = [
                    'product_id' => $product_id,
                    'quantity' => $item['quantity'],
                    'price_at_order' => $item_price, // Gunakan harga terbaru dari DB
                ];
                $total_amount += $item_price * $item['quantity'];

            } else {
                $stock_issues_found = true;
                $conn->rollback();
                $_SESSION['message'] = "Produk dengan ID " . $product_id . " tidak ditemukan di database dan telah dihapus dari keranjang Anda.";
                $_SESSION['message_type'] = "danger";
                // Hapus produk dari keranjang sesi agar tidak terulang
                unset($_SESSION['cart'][$product_id]);
                $stmt_product->close(); // Tutup statement sebelum koneksi ditutup
                $conn->close(); // Tutup koneksi sebelum redirect
                header("location: checkout.php");
                exit;
            }
            $stmt_product->close();
        } else {
            $stock_issues_found = true;
            $conn->rollback();
            $_SESSION['message'] = "Kesalahan database saat memeriksa produk: " . $conn->error;
            $_SESSION['message_type'] = "danger";
            $conn->close(); // Tutup koneksi sebelum redirect
            header("location: checkout.php");
            exit;
        }
    }

    // Jika tidak ada masalah stok dan keranjang tidak kosong, lanjutkan proses
    if (!$stock_issues_found && !empty($order_products)) {
        // Simpan data pesanan ke tabel 'orders'
        $sql_insert_order = "INSERT INTO orders (user_id, total_amount, status, shipping_address) VALUES (?, ?, ?, ?)";
        if ($stmt_order = $conn->prepare($sql_insert_order)) {
            $order_status = "pending"; // Status awal pesanan
            $stmt_order->bind_param("idss", $user_id, $total_amount, $order_status, $shipping_address_combined);

            if ($stmt_order->execute()) {
                $order_id = $conn->insert_id; // Dapatkan ID pesanan yang baru dibuat
                $stmt_order->close(); // Tutup statement order

                // Simpan setiap item pesanan ke tabel 'order_items' dan kurangi stok produk
                $sql_insert_item = "INSERT INTO order_items (order_id, product_id, quantity, price_at_order) VALUES (?, ?, ?, ?)";
                $sql_update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";

                foreach ($order_products as $item_data) {
                    // Masukkan item ke order_items
                    if ($stmt_item = $conn->prepare($sql_insert_item)) {
                        $stmt_item->bind_param("iiid", $order_id, $item_data['product_id'], $item_data['quantity'], $item_data['price_at_order']);
                        if (!$stmt_item->execute()) {
                            // Jika gagal, rollback dan keluar
                            $conn->rollback();
                            $_SESSION['message'] = "Gagal menyimpan detail item pesanan. Error: " . $stmt_item->error;
                            $_SESSION['message_type'] = "danger";
                            $stmt_item->close(); // Tutup statement sebelum koneksi ditutup
                            $conn->close(); // Tutup koneksi sebelum redirect
                            header("location: checkout.php");
                            exit;
                        }
                        $stmt_item->close();
                    } else {
                        $conn->rollback();
                        $_SESSION['message'] = "Gagal mempersiapkan query item pesanan. Error: " . $conn->error;
                        $_SESSION['message_type'] = "danger";
                        $conn->close(); // Tutup koneksi sebelum redirect
                        header("location: checkout.php");
                        exit;
                    }

                    // Kurangi stok produk
                    if ($stmt_stock_update = $conn->prepare($sql_update_stock)) {
                        $stmt_stock_update->bind_param("ii", $item_data['quantity'], $item_data['product_id']);
                        if (!$stmt_stock_update->execute()) {
                            // Jika gagal, rollback dan keluar
                            $conn->rollback();
                            $_SESSION['message'] = "Gagal mengurangi stok produk. Error: " . $stmt_stock_update->error;
                            $_SESSION['message_type'] = "danger";
                            $stmt_stock_update->close(); // Tutup statement sebelum koneksi ditutup
                            $conn->close(); // Tutup koneksi sebelum redirect
                            header("location: checkout.php");
                            exit;
                        }
                        $stmt_stock_update->close();
                    } else {
                        $conn->rollback();
                        $_SESSION['message'] = "Gagal mempersiapkan query update stok. Error: " . $conn->error;
                        $_SESSION['message_type'] = "danger";
                        $conn->close(); // Tutup koneksi sebelum redirect
                        header("location: checkout.php");
                        exit;
                    }
                }

                // Jika semua berhasil, commit transaksi
                $conn->commit();
                $conn->close(); // Tutup koneksi setelah commit

                // Kosongkan keranjang setelah pesanan berhasil
                unset($_SESSION['cart']);

                $_SESSION['message'] = "Pesanan Anda berhasil dibuat! Nomor pesanan Anda adalah #" . $order_id . ".";
                $_SESSION['message_type'] = "success";
                header("location: order_confirmation.php?order_id=" . $order_id); // Arahkan ke halaman konfirmasi
                exit;

            } else {
                // Gagal execute insert order
                $conn->rollback();
                $_SESSION['message'] = "Gagal membuat pesanan. Error: " . $stmt_order->error;
                $_SESSION['message_type'] = "danger";
                $stmt_order->close(); // Tutup statement sebelum koneksi ditutup
                $conn->close(); // Tutup koneksi sebelum redirect
                header("location: checkout.php");
                exit;
            }
        } else {
            // Gagal prepare insert order
            $conn->rollback();
            $_SESSION['message'] = "Gagal mempersiapkan query pesanan. Error: " . $conn->error;
            $_SESSION['message_type'] = "danger";
            $conn->close(); // Tutup koneksi sebelum redirect
            header("location: checkout.php");
            exit;
        }
    } else {
        // Ini seharusnya tidak tercapai jika validasi awal sudah bagus, tapi sebagai fallback
        $conn->rollback();
        $_SESSION['message'] = "Keranjang belanja kosong atau ada masalah stok yang tidak terdeteksi sebelumnya.";
        $_SESSION['message_type'] = "danger";
        $conn->close(); // Tutup koneksi sebelum redirect
        header("location: checkout.php");
        exit;
    }

} catch (Exception $e) {
    // Tangani exception lain jika terjadi
    $conn->rollback();
    $_SESSION['message'] = "Terjadi kesalahan tak terduga selama proses checkout: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    $conn->close(); // Tutup koneksi sebelum redirect
    header("location: checkout.php");
    exit;
}
?>