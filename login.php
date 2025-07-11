<?php

session_start();


if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php"); 
    exit;
}


require_once 'db_connect.php'; 

$username_email = $password = "";
$username_email_err = $password_err = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {

 
    if (empty(trim($_POST["username_email"]))) {
        $username_email_err = "Mohon masukkan username atau email.";
    } else {
        $username_email = trim($_POST["username_email"]);
    }


    if (empty(trim($_POST["password"]))) {
        $password_err = "Mohon masukkan password Anda.";
    } else {
        $password = trim($_POST["password"]);
    }

   
    if (empty($username_email_err) && empty($password_err)) {
        
        $sql = "SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?";

        if ($stmt = $conn->prepare($sql)) {
           
            $stmt->bind_param("ss", $param_username_email, $param_email); 
            
          
            $param_username_email = $username_email;
            $param_email = $username_email; 

          
            if ($stmt->execute()) {
                
                $stmt->store_result();

                
                if ($stmt->num_rows == 1) {                    
                    
                    $stmt->bind_result($id, $username, $email, $hashed_password, $role);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                           
                            session_start();
                            
                           
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["email"] = $email;
                            $_SESSION["role"] = $role; 

                           
                            if ($role === 'admin') {
                                header("location: admin/dashboard.php"); 
                            } else {
                                header("location: index.php"); 
                            }
                            exit;
                        } else {
                           
                            $password_err = "Password yang Anda masukkan salah.";
                        }
                    }
                } else {
                    
                    $username_email_err = "Username atau Email tidak ditemukan.";
                }
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
    <title>Login - Toko Boneka</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Login Akun</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="username_email">Username atau Email:</label>
                <input type="text" name="username_email" id="username_email" value="<?php echo htmlspecialchars($username_email); ?>">
                <span class="help-block"><?php echo $username_email_err; ?></span>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn-primary" value="Login">
            </div>
            <p class="register-link">Belum punya akun? <a href="register.php">Daftar sekarang</a>.</p>
        </form>
    </div>
</body>
</html>