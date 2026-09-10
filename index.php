<?php
// index.php
require_once 'config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
// database.php na ini-require sa taas

// Auto-redirect kung nakapag-login na (Clean URL format)
if (isset($_SESSION['admin_id'])) {
    header("Location: admin/index");
    exit();
}
if (isset($_SESSION['member_id'])) {
    header("Location: member/index");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please refresh and try again.";
    }
    
    $identifier = trim($_POST['username'] ?? '');
    $auth_key = trim($_POST['password'] ?? '');
    
    // Rate Limiting: Max 5 attempts per 15 minutes per IP
    if (empty($error) && !checkRateLimit('login_' . $_SERVER['REMOTE_ADDR'])) {
        $error = "Too many login attempts. Please try again after 15 minutes.";
    }

    if (!empty($identifier) && !empty($auth_key)) {
        
        // ATTEMPT 1: I-check kung Admin
        $stmt = $pdo->prepare("SELECT id, password_hash, role, full_name FROM admins WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $identifier]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($auth_key, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['role'] = $admin['role'];
            $_SESSION['full_name'] = $admin['full_name'];
            header("Location: admin/index");
            exit();
        }

        // ATTEMPT 2: I-check kung VIP Member (Username = Last Name, Password = QR Code)
        $memberStmt = $pdo->prepare("SELECT id, first_name, last_name, status FROM members WHERE last_name = :last_name AND qr_code = :qr_code LIMIT 1");
        $memberStmt->execute(['last_name' => $identifier, 'qr_code' => $auth_key]);
        $member = $memberStmt->fetch();

        if ($member) {
            if ($member['status'] === 'Suspended') {
                $error = "Your VIP account is suspended. Please contact staff.";
            } else {
                session_regenerate_id(true);
                $_SESSION['member_id'] = $member['id'];
                $_SESSION['member_name'] = $member['first_name'] . ' ' . $member['last_name'];
                header("Location: member/index");
                exit();
            }
        } else {
            // Kapag hindi Admin at hindi rin tumugma sa VIP records
            $error = "Invalid credentials. Please try again.";
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
    <title>EL1TE Pickle Center - Portal</title>

    <!-- Heto ang Favicon Code -->
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="login-container">
        <img src="assets/images/logo.jpg" alt="EL1TE Pickle Center Logo" class="login-logo">
        
        <h2>EL1TE Portal</h2>
        <p style="text-align: center; color: #cbd5e1; margin-bottom: 20px; font-size: 14px;">Staff Login & VIP Access</p>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Ginamit ang "index" lang para sa Clean URLs via .htaccess -->
        <form action="index" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="off">
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-gold">Access Portal</button>
        </form>
    </div>

</body>
</html>