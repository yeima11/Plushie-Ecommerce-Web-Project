<?php

session_start();


require_once 'db_connect.php'; 

$username_err = $email_err = $password_err = $confirm_password_err = "";
$username = $email = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    
    if (empty(trim($_POST["username"]))) {
        $username_err = "Mohon masukkan username.";
    } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", trim($_POST["username"]))) {
        $username_err = "Username hanya boleh mengandung huruf, angka, dan underscore.";
    } else {
       
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = trim($_POST["username"]);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $username_err = "Username ini sudah digunakan.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Ada yang salah. Silakan coba lagi nanti.";
            }
            $stmt->close();
        }
    }


    if (empty(trim($_POST["email"]))) {
        $email_err = "Mohon masukkan email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Format email tidak valid.";
    } else {
       
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = trim($_POST["email"]);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $email_err = "Email ini sudah terdaftar.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Ada yang salah. Silakan coba lagi nanti.";
            }
            $stmt->close();
        }
    }

   
    if (empty(trim($_POST["password"]))) {
        $password_err = "Mohon masukkan password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password minimal harus 6 karakter.";
    } else {
        $password = trim($_POST["password"]);
    }

  
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Mohon konfirmasi password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password tidak cocok.";
        }
    }


    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {

    
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            
            $stmt->bind_param("sss", $param_username, $param_email, $param_password);

           
            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT);

           
            if ($stmt->execute()) {
               
                header("location: login.php");
                exit();
            } else {
                echo "Ada yang salah. Silakan coba lagi nanti.";
            }
            $stmt->close();
        }
    }

   
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Toko Boneka</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="register-container">
        <h2>Daftar Akun Baru</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>">
                <span class="help-block"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password:</label>
                <input type="password" name="confirm_password" id="confirm_password">
                <span class="help-block"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn-primary" value="Daftar">
            </div>
            <p class="login-link">Sudah punya akun? <a href="login.php">Login di sini</a>.</p>
        </form>
    </div>
</body>
</html>