<?php
// scan.php
require_once 'config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// I-kick out ang user pabalik sa index kung hindi naka-login
if (!isset($_SESSION['admin_id'])) {
    header("Location: index");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EL1TE Pickle Center - Scanner</title>

    <!-- Heto ang Favicon Code -->
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">

    <link rel="stylesheet" href="assets/css/style.css">
    <!-- html5-qrcode library -->
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" type="text/javascript" crossorigin="anonymous"></script>
    <!-- SweetAlert2 library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body style="align-items: flex-start;"> <!-- Override center alignment for dashboard -->

    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="assets/images/logo.jpg" alt="Logo">
            <h1>EL1TE Dashboard</h1>
        </div>
        
        <!-- Burger Icon para sa Mobile -->
        <div class="menu-toggle" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <!-- Navigation Links -->
        <div class="nav-links" id="navLinks">
            <a href="admin/index">Dashboard</a>
            <a href="scan" class="active-link">Scanner</a>
            <a href="admin/members">VIP Members</a>
            <a href="admin/rewards">Rewards</a>
            <a href="admin/activities">Activities</a>
            <a href="admin/reports">Reports</a>
            <a href="#" class="logout-btn" onclick="confirmLogout(event, 'logout.php')">Logout</a>
        </div>
    </header>

    <div class="main-container">

    <!-- Search Module -->
        <div class="search-container">
            <input type="text" id="search-input" placeholder="Search Name, ID, or QR Code..." autocomplete="off">
            <div class="search-results" id="search-results"></div>
        </div>
        
        <!-- Scanner Module -->
        <div class="scanner-box" id="scanner-container">
            <h2 style="color: #D4AF37; margin-bottom: 15px;">Scan VIP Card</h2>
            <p style="color: #cbd5e1; margin-bottom: 15px; font-size: 14px;">Please verify physical card visually before scanning.</p>
            <div id="reader"></div>
            <button id="rescan-btn" class="btn-gold" style="display:none; margin-top: 15px;">Scan Another Card</button>
        </div>

        <!-- Member Profile Module (Hidden by default) -->
        <div class="member-card" id="member-profile">
            <div class="member-info">
                <h3 id="member-name">Loading...</h3>
                <p>Status: <strong id="member-status" style="color: #4ade80;">Active</strong></p>
                
                <div class="point-balance">
                    <span id="member-points">0</span> Points
                </div>
                <p style="font-size: 13px;">Next Redemption: <span id="member-lockout">Eligible Now</span></p>
            </div>

            <!-- Action Buttons based on Business Logic -->
            <div class="action-buttons" id="action-panel">
                <div id="dynamic-activity-buttons" style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Dito maglo-load ang buttons galing sa database via JS -->
                </div>
                <hr style="border-color: #334155; margin: 10px 0; width: 100%;">
                <button class="btn-gold" style="background-color: #f59e0b;" onclick="openRedeemModal()">Redeem Reward</button>
            </div>
        </div>

    </div>

    <!-- JavaScript Module -->
    <script src="assets/js/scanner.js"></script>

    <!-- Redemption Modal -->
    <div class="modal-overlay" id="redeem-modal">
        <div class="modal-content">
            <h3 style="color: #D4AF37; margin-bottom: 10px;">Redeem Reward</h3>
            <p style="color: #cbd5e1; font-size: 14px;">Warning: Redeeming any reward will reset all accumulated points to ZERO.</p>
            
            <select id="reward-dropdown">
                <option value="">Loading rewards...</option>
            </select>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="btn-gold" style="flex: 1;" onclick="submitRedemption()">Confirm</button>
                <button class="btn-gold btn-secondary" style="flex: 1;" onclick="closeRedeemModal()">Cancel</button>
            </div>
        </div>
    </div>

    <script>

        // Script para sa Burger Menu
        function toggleMobileMenu() {
            const nav = document.getElementById('navLinks');
            nav.classList.toggle('show-menu');
        }
        function confirmLogout(e, logoutUrl) {
            e.preventDefault();
            Swal.fire({
                title: 'Log out?',
                text: "Are you sure you want to log out of the system?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff6b6b',
                cancelButtonColor: '#334155',
                confirmButtonText: 'Yes, log me out',
                background: '#1A2A47',
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = logoutUrl;
                }
            });
        }
    </script>

    
<script>window.CSRF_TOKEN = '<?= generateCsrfToken() ?>';</script>
</body>
</html>