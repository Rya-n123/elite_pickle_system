<?php
// member/login.php
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['member_id'])) { header("Location: index"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EL1TE VIP - Member Portal</title>

    <!-- Heto ang Favicon Code (may ../ sa unahan) -->
    <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">

    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <div class="split-login-wrapper">
        
        <!-- KALIWANG SIDE: Branding -->
        <div class="split-image">
            <img src="../assets/images/logo.jpg" alt="EL1TE Logo" style="width: 130px; border-radius: 50%; border: 3px solid #D4AF37; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.5);">
            <h1>VIP Loyalty Club</h1>
            <p>Your exclusive access to premium rewards, match points, and special EL1TE Pickleball privileges.</p>
        </div>

        <!-- KANANG SIDE: Form -->
        <div class="split-form">
            <h2 style="color: #D4AF37; margin-bottom: 5px; text-align: center;">Member Portal</h2>
            <p style="text-align: center; color: #cbd5e1; margin-bottom: 25px; font-size: 14px;">Log in to check your points and rewards</p>
            
            <form id="memberLoginForm">
                <div class="input-group">
                    <label for="qr_code">VIP Card Number</label>
                    <input type="text" id="qr_code" placeholder="e.g. EPC-2026-001" required>
                </div>

                <div class="input-group">
                    <label for="last_name">Last Name</label>
                    <input type="password" id="last_name" placeholder="Enter your last name" required>
                </div>

                <button type="submit" class="btn-gold" style="margin-top: 15px;">Access My Account</button>
            </form>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('memberLoginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const qr_code = document.getElementById('qr_code').value.trim();
            const last_name = document.getElementById('last_name').value.trim();

            fetch('../api/member_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
                body: JSON.stringify({ qr_code, last_name })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) window.location.href = 'index';
                else Swal.fire('Error', data.error, 'error');
            });
        });
    </script>

    <script>window.CSRF_TOKEN = '<?= generateCsrfToken() ?>';</script>
</body>
</html>