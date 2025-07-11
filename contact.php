<?php
session_start();

// Inisialisasi variabel untuk status login dan username
$loggedin = false;
$username = "Tamu"; 
$user_role = "guest"; 

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $loggedin = true;
    $username = htmlspecialchars($_SESSION["username"]);
    $user_role = $_SESSION["role"];
}

// Logika untuk mengirim formulir kontak (Sederhana: hanya menampilkan pesan)
// Dalam aplikasi nyata, Anda akan memproses data ini (misalnya: kirim email, simpan ke database)
$form_message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (!empty($name) && !empty($email) && !empty($subject) && !empty($message)) {
        // Ini adalah tempat di mana Anda akan menambahkan logika pengiriman email nyata
        // Contoh: mail($to, $subject, $message, $headers);
        // Untuk demo, kita hanya akan menampilkan pesan sukses
        $form_message = 'Terima kasih, pesan Anda telah terkirim! Kami akan segera menghubungi Anda.';
        $message_type = 'success';
    } else {
        $form_message = 'Mohon lengkapi semua kolom formulir.';
        $message_type = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak Kami - BonekaKu</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>
    <header>
           <!-- TOP HEADER -->
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

<!-- NAVBAR MENU -->
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
        <div class="contact-container">
            <h2>Kontak Kami</h2>
            <p class="tagline">Kami siap membantu Anda! Jangan ragu untuk menghubungi kami.</p>

            <div class="contact-info">
                <h3>Informasi Kontak</h3>
                <p><strong>Alamat:</strong> Jl. Boneka Indah No. 123, Kota Boneka, 12345</p>
                <p><strong>Telepon:</strong> (021) 123-4567</p>
                <p><strong>Email:</strong> info@bonekaku.com</p>
                <p><strong>Jam Kerja:</strong> Senin - Jumat, 09:00 - 17:00 WIB</p>
            </div>

            <div class="contact-form-section">
                <h3>Kirim Pesan kepada Kami</h3>
                <?php if (!empty($form_message)): ?>
                    <div class="alert <?php echo $message_type; ?>"><?php echo $form_message; ?></div>
                <?php endif; ?>
                <form action="contact.php" method="POST" class="contact-form">
                    <div class="form-group">
                        <label for="name">Nama Lengkap:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Anda:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subjek:</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Pesan Anda:</label>
                        <textarea id="message" name="message" rows="6" required></textarea>
                    </div>
                    <button type="submit" class="btn-primary">Kirim Pesan</button>
                </form>
            </div>
        </div>
    </main>

      <footer class="footer">
  <div class="footer-container">
    <div class="footer-column">
      <h3>Support</h3>
      <ul>
        <li><a href="#">Contact us</a></li>
        <li><a href="#">Track Parcel</a></li>
        <li><a href="#">FAQs</a></li>
        <li><a href="#">Shipping Policy</a></li>
        <li><a href="#">Refund Policy</a></li>
        <li><a href="#">Payment</a></li>
      </ul>
    </div>
    <div class="footer-column">
      <h3>Learn</h3>
      <ul>
        <li><a href="#">About us</a></li>
        <li><a href="#">Our Blog</a></li>
        <li><a href="#">Meet our Ambassadors</a></li>
        <li><a href="#">Terms And Conditions</a></li>
        <li><a href="#">Our Promise Of Privacy</a></li>
      </ul>
    </div>
  </div>

  <div class="footer-social">
    <a href="#"><i class="fab fa-facebook"></i></a>
    <a href="#"><i class="fab fa-instagram"></i></a>
    <a href="#"><i class="fab fa-youtube"></i></a>
  </div>

  <div class="footer-bottom">
    <p>&copy; <?php echo date("Y"); ?> Toko Boneka Impian. Semua Hak Cipta Dilindungi.</p>
  </div>
</footer>
</body>
</html>