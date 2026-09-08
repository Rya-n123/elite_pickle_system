<?php
// index.php
session_start();
require_once 'config/database.php';

// Kung nakapag-login na, i-redirect agad sa main scanner dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: scan");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // Gumamit ng prepared statement para iwas SQL Injection
        $stmt = $pdo->prepare("SELECT id, password_hash, role FROM admins WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();

        // I-verify ang hash password
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // I-set ang session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['role'] = $admin['role'];
            
            // I-redirect sa scanner page
            header("Location: admin/index");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EL1TE Pickle Center - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="login-container">
        <!-- Tatawagin ang image gamit ang exact file name -->
        <img src="assets/images/logo.jpg" alt="EL1TE Pickle Center Logo" class="login-logo">
        
        <h2>System Login</h2>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="off">
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-gold">Login</button>
        </form>
    </div>

</body>
</html>