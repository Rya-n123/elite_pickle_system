<?php
// admin/reports.php
session_start();

if (!isset($_SESSION['admin_id'])) { header("Location: ../index"); exit(); }
require_once '../config/database.php';

// Para sa Analytics Summary Cards (Current Date and Month)
$today = date('Y-m-d');
$thisMonth = date('Y-m');

try {
    // KPI Queries
    $kpiPointsToday = $pdo->query("SELECT COALESCE(SUM(points_awarded), 0) FROM point_transactions WHERE DATE(transaction_date) = '$today'")->fetchColumn();
    $kpiPointsMonth = $pdo->query("SELECT COALESCE(SUM(points_awarded), 0) FROM point_transactions WHERE DATE_FORMAT(transaction_date, '%Y-%m') = '$thisMonth'")->fetchColumn();
    $kpiRedeemsMonth = $pdo->query("SELECT COUNT(id) FROM redemption_history WHERE DATE_FORMAT(redeemed_at, '%Y-%m') = '$thisMonth'")->fetchColumn();
    $kpiMembers = $pdo->query("SELECT COUNT(id) FROM members WHERE status = 'Active'")->fetchColumn();

    // 1. Point Transactions (Kasama na ang QR Code)
    $pointsStmt = $pdo->query("
        SELECT pt.activity_type, pt.points_awarded, pt.transaction_date, 
               m.first_name, m.last_name, m.qr_code, 
               a.full_name AS staff_name 
        FROM point_transactions pt
        JOIN members m ON pt.member_id = m.id
        JOIN admins a ON pt.admin_id = a.id
        ORDER BY pt.transaction_date DESC
    ");
    $pointLogs = $pointsStmt->fetchAll();

    // 2. Redemption History (Kasama ang points_required para sa computation ng excess)
    $redemptionsStmt = $pdo->query("
        SELECT rh.points_before, rh.redeemed_at, 
               m.first_name, m.last_name, m.qr_code, 
               r.reward_name, r.points_required, 
               a.full_name AS staff_name 
        FROM redemption_history rh
        JOIN members m ON rh.member_id = m.id
        JOIN rewards r ON rh.reward_id = r.id
        JOIN admins a ON rh.admin_id = a.id
        ORDER BY rh.redeemed_at DESC
    ");
    $redemptionLogs = $redemptionsStmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EL1TE Pickle Center - Reports</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .report-section {
            margin-bottom: 40px;
        }
        .report-header {
            color: #cbd5e1;
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 1px solid #334155;
            padding-bottom: 5px;
        }
    </style>
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
            <a href="index">Dashboard</a>
            <a href="../scan">Scanner</a>
            <a href="members">VIP Members</a>
            <a href="rewards">Rewards</a>
            <a href="activities">Activities</a>
            <a href="reports" class="active-link">Reports</a>
            <a href="#" class="logout-btn" onclick="confirmLogout(event, '../logout.php')">Logout</a>
        </div>
    </header>

    <div class="main-container">
        
        <!-- Analytics Summary Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <h4>Points Issued Today</h4>
                <div class="kpi-value">+<?= $kpiPointsToday ?></div>
            </div>
            <div class="kpi-card">
                <h4>Points Issued This Month</h4>
                <div class="kpi-value">+<?= $kpiPointsMonth ?></div>
            </div>
            <div class="kpi-card">
                <h4>Rewards Claimed (Month)</h4>
                <div class="kpi-value"><?= $kpiRedeemsMonth ?></div>
            </div>
            <div class="kpi-card">
                <h4>Total Active VIPs</h4>
                <div class="kpi-value"><?= $kpiMembers ?></div>
            </div>
        </div>

        <!-- Global Date Filter -->
        <div style="background-color: #1A2A47; padding: 15px 20px; border-radius: 8px; margin-bottom: 30px; display: flex; gap: 15px; align-items: center; border-left: 4px solid #D4AF37; flex-wrap: wrap;">
            <strong style="color: #D4AF37;">Filter by Date:</strong>
            <input type="date" id="minDate" style="background: #3157b1; color: #fff; border: 1px solid #334155; border-radius: 4px; padding: 5px 10px;">
            <span style="color: #cbd5e1;">to</span>
            <input type="date" id="maxDate" style="background: #3157b1; color: #fff; border: 1px solid #334155; border-radius: 4px; padding: 5px 10px;">
            <button class="btn-gold" style="margin: 0; padding: 6px 15px; width: auto;" onclick="applyDateFilter()">Apply Filter</button>
            <button class="btn-gold" style="margin: 0; padding: 6px 15px; width: auto; background-color: #334155; color: #fff;" onclick="clearDateFilter()">Clear</button>
        </div>

        <!-- Redemption Log Section -->
        <div class="table-container report-section">
            <h2 style="color: #D4AF37; margin-bottom: 15px;">Reward Redemptions</h2>
            <div class="report-header">Complete history of VIP reward claims and point resets.</div>
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>VIP Card Number</th>
                        <th>Member Name</th>
                        <th>Reward Claimed</th>
                        <th>Reward Cost</th>
                        <th>Excess Forfeited</th>
                        <th>Processed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($redemptionLogs) > 0): ?>
                        <?php foreach ($redemptionLogs as $log): 
                            $cost = (int)$log['points_required'];
                            $pointsBefore = (int)$log['points_before'];
                            $excessForfeited = max(0, $pointsBefore - $cost);
                        ?>
                            <tr>
                                <td data-date="<?= date('Y-m-d', strtotime($log['redeemed_at'])) ?>" style="color: #cbd5e1; font-size: 13px;"><?= date('M d, Y h:i A', strtotime($log['redeemed_at'])) ?></td>
                                <td style="font-family: monospace; color: #94a3b8;"><?= htmlspecialchars($log['qr_code']) ?></td>
                                <td><strong><?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?></strong></td>
                                <td style="color: #D4AF37; font-weight: bold;"><?= htmlspecialchars($log['reward_name']) ?></td>
                                <td style="color: #38bdf8; font-weight: bold;"><?= $cost ?> pts</td>
                                <td style="color: #ff6b6b; font-weight: bold;">
                                    <?= $excessForfeited > 0 ? '-' . $excessForfeited . ' pts' : '0 pts' ?>
                                </td>
                                <td style="font-size: 13px;"><?= htmlspecialchars($log['staff_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; color: #cbd5e1; padding: 20px;">No redemptions recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Point Transaction Log Section -->
        <div class="table-container report-section">
            <h2 style="color: #D4AF37; margin-bottom: 15px;">Point Transactions</h2>
            <div class="report-header">Complete history of point additions.</div>
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>VIP Card Number</th>
                        <th>Member Name</th>
                        <th>Activity</th>
                        <th>Points Earned</th>
                        <th>Processed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pointLogs) > 0): ?>
                        <?php foreach ($pointLogs as $log): ?>
                            <tr>
                                <td data-date="<?= date('Y-m-d', strtotime($log['transaction_date'])) ?>" style="color: #cbd5e1; font-size: 13px;"><?= date('M d, Y h:i A', strtotime($log['transaction_date'])) ?></td>
                                <td style="font-family: monospace; color: #94a3b8;"><?= htmlspecialchars($log['qr_code']) ?></td>
                                <td><strong><?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?></strong></td>
                                <td>
                                    <span class="badge" style="background: rgba(212, 175, 55, 0.1); color: #D4AF37; border: 1px solid #D4AF37; display: inline-block; white-space: nowrap;">
                                        <?= htmlspecialchars($log['activity_type']) ?>
                                    </span>
                                </td>
                                <td style="color: #4ade80; font-weight: bold;">+<?= $log['points_awarded'] ?></td>
                                <td style="font-size: 13px;"><?= htmlspecialchars($log['staff_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; color: #cbd5e1; padding: 20px;">No point transactions recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Isiningit ang jQuery at DataTables scripts dito -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Custom DataTables Date Range Search Filter
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex, rowData, counter) {
                let min = $('#minDate').val();
                let max = $('#maxDate').val();
                
                // Kunin ang hidden data-date attribute sa pinaka-unang column (Date & Time)
                let rowDate = $(settings.aoData[dataIndex].nTr).find('td:eq(0)').attr('data-date');

                if (!min && !max) { return true; }
                if (min && !max && rowDate >= min) { return true; }
                if (!min && max && rowDate <= max) { return true; }
                if (min && max && rowDate >= min && rowDate <= max) { return true; }
                
                return false;
            }
        );

        let dtTables; // Global variable para ma-access ng filter buttons
        
        // Initialize DataTables
        $(document).ready(function() {
            dtTables = $('table').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "language": {
                    "search": "Keyword search:"
                }
            });
        });

        // Function para i-trigger ang filter
        function applyDateFilter() {
            dtTables.draw(); // Nire-redraw nito ang table para gumana yung custom search filter sa taas
        }

        // Function para i-reset ang filter
        function clearDateFilter() {
            $('#minDate').val('');
            $('#maxDate').val('');
            dtTables.draw();
        }
        function toggleMobileMenu() {
            const nav = document.getElementById('navLinks');
            nav.classList.toggle('show-menu');
        }

        function confirmLogout(e, logoutUrl) {
            e.preventDefault();
            Swal.fire({
                title: 'Log out?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff6b6b',
                cancelButtonColor: '#334155',
                confirmButtonText: 'Yes, log me out',
                background: '#1A2A47',
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = logoutUrl;
            });
        }
    </script>
</body>
</html>