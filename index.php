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

    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    
    <!-- Google Fonts & Tailwind CSS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] }, colors: { gold: '#D4AF37' } } }
      }
    </script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center font-sans p-4">

    <!-- SPLIT SCREEN WRAPPER -->
    <div class="flex flex-col md:flex-row w-full max-w-5xl bg-slate-800 rounded-2xl shadow-2xl overflow-hidden border-t-4 md:border-t-0 md:border-l-4 border-gold">
        
        <!-- KALIWANG SIDE: Branding -->
        <!-- Note: Gumamit ako ng Tailwind arbitrary value para sa background image gradient -->
        <div class="md:w-[55%] relative flex flex-col justify-center items-center p-10 text-center bg-[linear-gradient(to_right,rgba(17,28,48,0.7),rgba(26,42,71,0.9)),url('assets/images/login-bg.jpg')] bg-cover bg-center">
            <img src="assets/images/logo.jpg" alt="EL1TE Logo" class="w-32 h-32 rounded-full border-4 border-gold shadow-lg mb-6 object-cover">
            <h1 class="text-3xl font-bold text-gold mb-3 drop-shadow-md">EL1TE VIP Club</h1>
            <p class="text-slate-300 text-sm leading-relaxed max-w-sm drop-shadow-md">
                Welcome to your exclusive portal. Track your match points, view your rank, and redeem premium Pickleball rewards.
            </p>
        </div>

        <!-- KANANG SIDE: Form -->
        <div class="md:w-[45%] flex flex-col justify-center p-8 md:p-12 bg-slate-800/90">
            <h2 class="text-2xl font-bold text-gold mb-1 text-center">Member Access</h2>
            <p class="text-slate-400 text-sm mb-8 text-center">Enter your account details to continue</p>

            <?php if (!empty($error)): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 p-3 rounded-lg text-sm mb-6 text-center">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="index" method="POST" class="flex flex-col gap-5">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                
                <div>
                    <label for="username" class="block text-sm text-slate-300 mb-1.5 font-medium">Username / Last Name</label>
                    <input type="text" id="username" name="username" placeholder="Enter Username or Last Name" required autocomplete="off" 
                           class="w-full p-3 bg-slate-900 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold transition-all">
                </div>

                <div>
                    <label for="password" class="block text-sm text-slate-300 mb-1.5 font-medium">Password / VIP Card No.</label>
                    <input type="password" id="password" name="password" placeholder="Enter Password or VIP Card" required 
                           class="w-full p-3 bg-slate-900 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-gold focus:ring-1 focus:ring-gold transition-all">
                </div>

                <button type="submit" class="w-full py-3 mt-4 bg-gold hover:bg-yellow-500 text-slate-900 font-bold rounded-lg shadow-lg hover:-translate-y-0.5 transition-all active:scale-95">
                    Access My Account
                </button>
            </form>
        </div>

    </div>

</body>
</html>