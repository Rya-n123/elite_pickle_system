<?php
// member/index.php
session_start();
if (!isset($_SESSION['member_id'])) { header("Location: login"); exit(); }
require_once '../config/database.php';

$member_id = $_SESSION['member_id'];

try {
    // 1. Kunin ang Points at Cooldown
    $stmt = $pdo->prepare("SELECT point_balance, next_eligible_date FROM members WHERE id = :id");
    $stmt->execute(['id' => $member_id]);
    $member = $stmt->fetch();

    // 2. Kunin ang Recent Transactions
    $transStmt = $pdo->prepare("SELECT activity_type, points_awarded, transaction_date FROM point_transactions WHERE member_id = :id ORDER BY transaction_date DESC LIMIT 5");
    $transStmt->execute(['id' => $member_id]);
    $transactions = $transStmt->fetchAll();

    // 3. Kunin ang Available Rewards
    $rewStmt = $pdo->query("SELECT reward_name, points_required FROM rewards WHERE status = 'Available' ORDER BY points_required ASC");
    $rewards = $rewStmt->fetchAll();

} catch (PDOException $e) {
    die("Database error.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EL1TE VIP - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .portal-header { background-color: #1A2A47; padding: 20px; border-bottom: 2px solid #D4AF37; text-align: center; }
        .points-display { font-size: 60px; font-weight: bold; color: #D4AF37; margin: 10px 0; }
        .reward-card { background: #0f172a; padding: 15px; border-radius: 8px; border: 1px solid #334155; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body style="padding-top: 0;"> 

    <div class="portal-header">
        <img src="../assets/images/logo.jpg" alt="Logo" style="width: 60px; border-radius: 50%; border: 1px solid #D4AF37; margin-bottom: 10px;">
        <h3 style="margin: 0; color: #fff;">Hello, <?= htmlspecialchars($_SESSION['member_name']) ?>!</h3>
        <a href="logout" style="color: #ff6b6b; font-size: 14px; text-decoration: none; margin-top: 5px; display: inline-block;">Log out</a>
    </div>

    <div class="main-container" style="max-width: 600px; padding: 20px;">
        
        <!-- Strict Physical Card Warning -->
        <div style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <strong style="color: #f59e0b; display: block; margin-bottom: 5px;">⚠️ Physical VIP Card Required</strong>
            <span style="color: #cbd5e1; font-size: 13px;">Digital copies or screenshots will NOT be accepted. Please present your actual physical VIP card at the front desk to earn or redeem points.</span>
        </div>

        <div style="background-color: #1A2A47; padding: 30px 20px; border-radius: 12px; text-align: center; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
            <div style="color: #cbd5e1; text-transform: uppercase; letter-spacing: 1px; font-size: 14px;">Current Balance</div>
            <div class="points-display"><?= $member['point_balance'] ?></div>
            <div style="color: #4ade80; font-size: 13px;">
                <?php 
                    if (empty($member['next_eligible_date']) || $member['next_eligible_date'] <= date('Y-m-d')) {
                        echo 'You are currently eligible to redeem a reward!';
                    } else {
                        echo '<span style="color:#ff6b6b;">Next redemption available on: ' . date('M d, Y', strtotime($member['next_eligible_date'])) . '</span>';
                    }
                ?>
            </div>
        </div>

        <h3 style="color: #D4AF37; border-bottom: 1px solid #334155; padding-bottom: 10px; margin-bottom: 15px;">Rewards Catalog</h3>
        <div style="margin-bottom: 30px;">
            <?php foreach ($rewards as $rew): ?>
                <div class="reward-card">
                    <strong style="color: #fff;"><?= htmlspecialchars($rew['reward_name']) ?></strong>
                    <span style="color: <?= $member['point_balance'] >= $rew['points_required'] ? '#4ade80' : '#64748b' ?>; font-weight: bold;">
                        <?= $rew['points_required'] ?> pts
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 style="color: #D4AF37; border-bottom: 1px solid #334155; padding-bottom: 10px; margin-bottom: 15px;">Recent Points Earned</h3>
        <table style="width: 100%; text-align: left; margin-bottom: 40px;">
            <?php if(count($transactions) > 0): ?>
                <?php foreach($transactions as $trx): ?>
                    <tr style="border-bottom: 1px solid #1e293b;">
                        <td style="padding: 10px 0;">
                            <strong style="color: #cbd5e1; display: block;"><?= htmlspecialchars($trx['activity_type']) ?></strong>
                            <span style="font-size: 12px; color: #64748b;"><?= date('M d, Y', strtotime($trx['transaction_date'])) ?></span>
                        </td>
                        <td style="color: #4ade80; font-weight: bold; text-align: right;">+<?= $trx['points_awarded'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td style="color: #64748b; padding: 10px 0;">No transactions yet.</td></tr>
            <?php endif; ?>
        </table>

    </div>
</body>
</html>