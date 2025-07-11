<?php
session_start();
$loggedin = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$username = $loggedin ? $_SESSION['username'] : '';
$user_role = $loggedin ? $_SESSION['role'] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Syarat dan Ketentuan - BonekaKu</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="header-top">
            <div class="logo-kawaii">
                <img src="assets/images/plushie.png" alt="Logo" />
            </div>
            <div class="right-icons">
                <?php if ($loggedin): ?>
                    <span>Selamat datang, <?php echo $username; ?>! (<?php echo ucfirst($user_role); ?>)</span>
                    <?php if ($user_role === 'admin'): ?>
                        <a href="admin/dashboard.php">Dashboard Admin</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php"><i class="fa-solid fa-user"></i></a>
                    <a href="register.php">Sign In</a>
                <?php endif; ?>
            </div>
        </div>

        <nav class="navbar">
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="products.php">Shop All</a>
                <a href="new.php">New ✨</a>
                <a href="best_seller.php">Best Seller</a>
                <a href="contact.php">About Us</a>
                <a href="cart.php"><i class="fa-solid fa-bag-shopping"></i></a>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <section class="terms-section">
            <h2>Syarat dan Ketentuan</h2>
            <p>Dengan menggunakan situs web kami, Anda menyetujui untuk mematuhi syarat dan ketentuan berikut:</p>

            <h3>1. Penggunaan Situs</h3>
            <p>Semua konten di situs ini hanya untuk keperluan informasi dan pembelian produk BonekaKu. Dilarang menggunakan situs ini untuk tujuan ilegal atau merugikan pihak lain.</p>

            <h3>2. Harga dan Ketersediaan</h3>
            <p>Harga produk dapat berubah sewaktu-waktu tanpa pemberitahuan sebelumnya. Kami berusaha menampilkan stok produk dengan akurat, namun tidak menjamin bahwa semua produk tersedia setiap saat.</p>

            <h3>3. Pembayaran dan Pengiriman</h3>
            <p>Kami menerima pembayaran melalui metode yang tersedia di halaman checkout. Pengiriman dilakukan melalui jasa ekspedisi terpercaya dan nomor resi akan diberikan.</p>

            <h3>4. Pengembalian dan Refund</h3>
            <p>Anda dapat mengajukan pengembalian produk maksimal 7 hari setelah barang diterima, dengan syarat produk belum dibuka atau rusak. Silakan lihat <a href="refund_policy.php">kebijakan refund</a> untuk informasi lengkap.</p>

            <h3>5. Perubahan Ketentuan</h3>
            <p>Kami berhak mengubah isi dari Syarat dan Ketentuan ini kapan saja. Perubahan akan ditampilkan di halaman ini tanpa pemberitahuan langsung.</p>

            <p>Jika Anda memiliki pertanyaan, silakan hubungi kami melalui <a href="contact.php">halaman kontak</a>.</p>
        </section>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Toko Boneka Impian. Semua Hak Cipta Dilindungi.</p>
    </footer>
</body>
</html>
