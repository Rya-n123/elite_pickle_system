<?php
// member/index.php
session_start();

if (!isset($_SESSION['member_id'])) {
    header("Location: ../index");
    exit();
}

require_once '../config/database.php';
$member_id = $_SESSION['member_id'];

try {
    $stmt = $pdo->prepare("SELECT point_balance, next_eligible_date, qr_code, status FROM members WHERE id = :id");
    $stmt->execute(['id' => $member_id]);
    $member = $stmt->fetch();

    $transStmt = $pdo->prepare("
        SELECT activity_type, points_awarded, transaction_date 
        FROM point_transactions 
        WHERE member_id = :id 
        ORDER BY transaction_date DESC 
        LIMIT 5
    ");
    $transStmt->execute(['id' => $member_id]);
    $transactions = $transStmt->fetchAll();

    $rewStmt = $pdo->query("SELECT reward_name, points_required FROM rewards WHERE status = 'Available' ORDER BY points_required ASC");
    $rewards = $rewStmt->fetchAll();

    // Hanapin ang susunod na reward na pwede nilang pag-ipunan
    $next_reward = null;
    foreach ($rewards as $rew) {
        if ($rew['points_required'] > $member['point_balance']) {
            $next_reward = $rew;
            break;
        }
    }

    if ($next_reward) {
        $points_needed = $next_reward['points_required'] - $member['point_balance'];
        $progress_percentage = ($member['point_balance'] / $next_reward['points_required']) * 100;
        $progress_msg = "<strong>{$points_needed} pts</strong> away from a <strong>" . htmlspecialchars($next_reward['reward_name']) . "</strong>!";
    } else {
        if (count($rewards) > 0) {
            $progress_percentage = 100;
            $progress_msg = "You have enough points for any reward in the catalog!";
        } else {
            $progress_percentage = 0;
            $progress_msg = "More rewards coming soon!";
        }
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>EL1TE VIP - Dashboard</title>
    <!-- Ginamit ang bagong dedicated CSS file -->
    <link rel="stylesheet" href="../assets/css/member.css">
</head>
<body> 

    <header class="portal-header">
        <img src="../assets/images/logo.jpg" alt="Logo">
        <h3>Hello, <?= htmlspecialchars($_SESSION['member_name']) ?>!</h3>
        <div style="display: block; justify-content: center; gap: 15px; margin-top: 8px;">
            <a href="#" onclick="openProfileModal(event)" style="color: #cbd5e1; font-size: 13px; text-decoration: none; font-weight: 600;">👤 My Profile</a>
            <span style="color: #334155;">|</span>
            <a href="logout" class="btn-logout">Log out</a>
        </div>
    </header>

    <div class="member-container">
        
        <div class="warning-banner">
            <strong>⚠️ Physical VIP Card Required</strong>
            <span>Digital copies or screenshots will NOT be accepted. Please present your actual physical VIP card at the front desk to earn or redeem points.</span>
        </div>

        <!-- Premium VIP Point Card -->
        <div class="vip-card">
            <div class="label">Current Balance</div>
            <!-- Nilagyan natin ng id="pointCounter" at data-target -->
            <div class="points" id="pointCounter" data-target="<?= $member['point_balance'] ?>">0</div>
            
            <div class="status">
                <?php 
                    if (empty($member['next_eligible_date']) || $member['next_eligible_date'] <= date('Y-m-d')) {
                        echo '<span style="color: #4ade80;">✅ You are eligible to redeem a reward!</span>';
                    } else {
                        echo '<span style="color:#f87171;">⏳ Next redemption on: ' . date('M d, Y', strtotime($member['next_eligible_date'])) . '</span>';
                    }
                ?>
            </div>

            <!-- Bagong Progress Bar Section -->
            <div class="progress-container">
                <div class="progress-text"><?= $progress_msg ?></div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" id="progressBar" data-width="<?= $progress_percentage ?>%"></div>
                </div>
            </div>
        </div>

        <h3 class="section-title">Rewards Catalog</h3>
        <div>
            <?php foreach ($rewards as $rew): ?>
                <?php $is_affordable = $member['point_balance'] >= $rew['points_required']; ?>
                <div class="reward-card">
                    <span class="reward-name"><?= htmlspecialchars($rew['reward_name']) ?></span>
                    <span class="reward-points" style="color: <?= $is_affordable ? '#4ade80' : '#94a3b8' ?>;">
                        <?= $rew['points_required'] ?> pts
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 class="section-title">Recent Activity</h3>
        <table class="activity-list">
            <?php if(count($transactions) > 0): ?>
                <?php foreach($transactions as $trx): ?>
                    <tr>
                        <td>
                            <span class="activity-title"><?= htmlspecialchars($trx['activity_type']) ?></span>
                            <span class="activity-date"><?= date('M d, Y • h:i A', strtotime($trx['transaction_date'])) ?></span>
                        </td>
                        <td>+<?= $trx['points_awarded'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2" style="text-align: center; color: #64748b; font-size: 13px;">No activities recorded yet.</td>
                </tr>
            <?php endif; ?>
        </table>

    </div>

    <!-- My Profile Modal -->
    <div id="profileModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s;">
        <div class="modal-content" style="background: #1e293b; padding: 30px 25px; border-radius: 16px; width: 90%; max-width: 350px; text-align: center; border: 1px solid rgba(212, 175, 55, 0.3); box-shadow: 0 10px 25px rgba(0,0,0,0.5); transform: translateY(20px); transition: transform 0.3s;">
            
            <div style="width: 60px; height: 60px; background: rgba(212, 175, 55, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; border: 2px solid #D4AF37;">
                <span style="font-size: 24px;">👤</span>
            </div>
            
            <h3 style="color: #f8fafc; margin: 0 0 5px; font-size: 20px;"><?= htmlspecialchars($_SESSION['member_name']) ?></h3>
            <p style="color: #4ade80; margin: 0 0 20px; font-size: 13px; font-weight: 600;">● <?= $member['status'] ?> Member</p>

            <div style="background: #0f172a; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #334155; text-align: left;">
                <div style="margin-bottom: 10px;">
                    <span style="display: block; color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">VIP Card Number</span>
                    <?php 
                        // Masking the QR Code for security (e.g., ELITE-****12)
                        $qr = $member['qr_code'];
                        $masked_qr = substr($qr, 0, 6) . '****' . substr($qr, -2);
                    ?>
                    <strong style="color: #f8fafc; font-size: 15px; font-family: monospace;"><?= $masked_qr ?></strong>
                </div>
            </div>

            <button onclick="closeProfileModal()" style="background: #D4AF37; color: #0f172a; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer;">Close</button>
        </div>
    </div>

    
<script>
        // Gamified Number Counter Animation
        document.addEventListener("DOMContentLoaded", () => {
            const counter = document.getElementById('pointCounter');
            const target = +counter.getAttribute('data-target');
            const duration = 1000; // 1 second bago matapos ang bilang
            const frameRate = 30; // Ilang beses mag uupdate per second
            const totalFrames = Math.round(duration / (1000 / frameRate));
            let currentFrame = 0;

            if (target > 0) {
                const countInterval = setInterval(() => {
                    currentFrame++;
                    const progress = currentFrame / totalFrames;
                    
                    // Ease-out effect (bumabagal habang papalapit sa target)
                    const currentCount = Math.round(target * (1 - Math.pow(1 - progress, 3)));
                    
                    counter.innerText = currentCount;

                    if (currentFrame >= totalFrames) {
                        counter.innerText = target;
                        clearInterval(countInterval);
                    }
                }, 1000 / frameRate);
            } else {
                counter.innerText = target;
            }

            // Progress Bar Fill Animation (may delay ng konti para mas dramatic)
            setTimeout(() => {
                const pb = document.getElementById('progressBar');
                pb.style.width = pb.getAttribute('data-width');
            }, 400);

        });

        // Profile Modal Logic
        function openProfileModal(e) {
            e.preventDefault();
            const modal = document.getElementById('profileModal');
            const content = modal.querySelector('.modal-content');
            modal.style.display = 'flex';
            // Trigger animation
            setTimeout(() => {
                modal.style.opacity = '1';
                content.style.transform = 'translateY(0)';
            }, 10);
        }

        function closeProfileModal() {
            const modal = document.getElementById('profileModal');
            const content = modal.querySelector('.modal-content');
            modal.style.opacity = '0';
            content.style.transform = 'translateY(20px)';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    </script>
</body>
</html>