<?php
// admin/index.php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: ../index"); exit(); }
require_once '../config/database.php';

$today = date('Y-m-d');

try {
    // 1. Kumuha ng Quick KPIs para sa araw na ito
    $kpiMembers = $pdo->query("SELECT COUNT(id) FROM members WHERE status = 'Active'")->fetchColumn();
    $kpiPointsToday = $pdo->query("SELECT COALESCE(SUM(points_awarded), 0) FROM point_transactions WHERE DATE(transaction_date) = '$today'")->fetchColumn();
    $kpiRedeemsToday = $pdo->query("SELECT COUNT(id) FROM redemption_history WHERE DATE(redeemed_at) = '$today'")->fetchColumn();

    // 2. Kumuha ng Top 5 VIP Members
    $topMembersStmt = $pdo->query("
        SELECT first_name, last_name, point_balance 
        FROM members 
        WHERE status = 'Active' 
        ORDER BY point_balance DESC 
        LIMIT 5
    ");
    $topMembers = $topMembersStmt->fetchAll();

    // 3. Kumuha ng 5 Most Recent Transactions (Points Added)
    $recentActivityStmt = $pdo->query("
        SELECT pt.activity_type, pt.points_awarded, pt.transaction_date, m.first_name, m.last_name 
        FROM point_transactions pt
        JOIN members m ON pt.member_id = m.id
        ORDER BY pt.transaction_date DESC 
        LIMIT 5
    ");
    $recentActivities = $recentActivityStmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EL1TE Pickle Center - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="align-items: flex-start; padding-top: 80px;"> 

    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="../assets/images/logo.jpg" alt="Logo">
            <h1>EL1TE Dashboard</h1>
        </div>
        
        <div class="menu-toggle" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="nav-links" id="navLinks">
            <a href="index" class="active-link">Dashboard</a>
            <a href="../scan">Scanner</a>
            <a href="members">VIP Members</a>
            <a href="rewards">Rewards</a>
            <a href="activities">Activities</a>
            <a href="reports">Reports</a>
            <a href="#" class="logout-btn" onclick="confirmLogout(event, '../logout.php')">Logout</a>
        </div>
    </header>

    <div class="main-container">
        <h2 style="color: #fff; margin-bottom: 20px;">Welcome back, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?>!</h2>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card" style="border-top-color: #3b82f6;">
                <h4>Total Active VIPs</h4>
                <div class="kpi-value" style="color: #3b82f6;"><?= $kpiMembers ?></div>
            </div>
            <div class="kpi-card" style="border-top-color: #4ade80;">
                <h4>Points Issued Today</h4>
                <div class="kpi-value" style="color: #4ade80;">+<?= $kpiPointsToday ?></div>
            </div>
            <div class="kpi-card" style="border-top-color: #f59e0b;">
                <h4>Rewards Claimed Today</h4>
                <div class="kpi-value" style="color: #f59e0b;"><?= $kpiRedeemsToday ?></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Left Side: Recent Activity Feed -->
            <div class="dashboard-panel">
                <div class="flex-between" style="margin-bottom: 15px;">
                    <h3 style="color: #D4AF37; margin: 0;">Recent Transactions</h3>
                    <a href="reports" style="color: #cbd5e1; font-size: 12px; text-decoration: none;">View All</a>
                </div>
                <table style="min-width: 100%;">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Activity</th>
                            <th>Points</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recentActivities) > 0): ?>
                            <?php foreach ($recentActivities as $act): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($act['first_name'] . ' ' . $act['last_name']) ?></strong></td>
                                    <td style="font-size: 13px; color: #cbd5e1;"><?= htmlspecialchars($act['activity_type']) ?></td>
                                    <td style="color: #4ade80; font-weight: bold;">+<?= $act['points_awarded'] ?></td>
                                    <td style="font-size: 12px; color: #94a3b8;"><?= date('h:i A', strtotime($act['transaction_date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">No transactions today.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Right Side: VIP Leaderboard -->
            <div class="dashboard-panel">
                <h3 style="color: #D4AF37; margin-top: 0; margin-bottom: 15px;">VIP Leaderboard</h3>
                <ul class="leaderboard-list">
                    <?php if (count($topMembers) > 0): ?>
                        <?php $rank = 1; foreach ($topMembers as $member): ?>
                            <li class="rank-<?= $rank ?>">
                                <div style="display: flex; gap: 15px; align-items: center;">
                                    <div class="rank-badge"><?= $rank ?></div>
                                    <div style="color: #fff; font-weight: bold;">
                                        <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>
                                    </div>
                                </div>
                                <div style="color: #D4AF37; font-weight: bold; font-size: 18px;">
                                    <?= $member['point_balance'] ?> pts
                                </div>
                            </li>
                        <?php $rank++; endforeach; ?>
                    <?php else: ?>
                        <li style="color: #64748b; justify-content: center;">No active members yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function toggleMobileMenu() { document.getElementById('navLinks').classList.toggle('show-menu'); }
        function confirmLogout(e, logoutUrl) {
            e.preventDefault();
            Swal.fire({
                title: 'Log out?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ff6b6b', cancelButtonColor: '#334155', confirmButtonText: 'Yes, log me out', background: '#1A2A47', color: '#ffffff'
            }).then((result) => { if (result.isConfirmed) window.location.href = logoutUrl; });
        }
    </script>
</body>
</html>