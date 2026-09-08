<?php
// member/login.php
session_start();
if (isset($_SESSION['member_id'])) { header("Location: index"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EL1TE VIP - Member Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="display: flex; justify-content: center; align-items: center; height: 100vh;">
    <div class="login-container" style="text-align: center; max-width: 400px; width: 90%;">
        <img src="../assets/images/logo.jpg" alt="Logo" style="width: 100px; border-radius: 50%; margin-bottom: 20px; border: 2px solid #D4AF37;">
        <h2 style="color: #D4AF37; margin-bottom: 5px;">VIP Portal</h2>
        <p style="color: #cbd5e1; margin-bottom: 25px; font-size: 14px;">Log in to check your points and rewards</p>
        
        <form id="memberLoginForm">
            <input type="text" id="qr_code" placeholder="VIP Card Number (e.g. ELITE-10001)" required style="width: 100%; padding: 12px; margin-bottom: 15px; background: #0f172a; color: #fff; border: 1px solid #334155; border-radius: 6px;">
            <input type="password" id="last_name" placeholder="Last Name" required style="width: 100%; padding: 12px; margin-bottom: 20px; background: #0f172a; color: #fff; border: 1px solid #334155; border-radius: 6px;">
            <button type="submit" class="btn-gold" style="width: 100%;">Access My Account</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('memberLoginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const qr_code = document.getElementById('qr_code').value.trim();
            const last_name = document.getElementById('last_name').value.trim();

            fetch('../api/member_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ qr_code, last_name })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) window.location.href = 'index';
                else Swal.fire('Error', data.error, 'error');
            });
        });
    </script>
</body>
</html>