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
    error_log("Member Dashboard DB Error: " . $e->getMessage());
    die("System error. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>EL1TE VIP - Dashboard</title>

    <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
    
    <!-- Google Fonts, FontAwesome & Tailwind CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] }, colors: { gold: '#D4AF37' } } }
      }
    </script>
    <style>
        /* Smooth animation para sa Progress Bar */
        #progressBar { transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1); }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 font-sans pb-10"> 

    <!-- Header -->
    <header class="bg-slate-900/90 backdrop-blur-md border-b border-gold/30 sticky top-0 z-50 shadow-lg">
        <div class="max-w-md mx-auto px-5 py-4 flex flex-col items-center text-center">
            <img src="../assets/images/logo.jpg" alt="Logo" class="w-12 h-12 rounded-full border-2 border-gold shadow-md mb-2 object-cover">
            <h3 class="text-xl font-bold text-white mb-2">Hello, <span class="text-gold"><?= htmlspecialchars($_SESSION['member_name']) ?></span>!</h3>
            <div class="flex items-center justify-center gap-4 text-sm font-medium">
                <a href="#" onclick="openProfileModal(event)" class="text-slate-300 hover:text-gold transition-colors"><i class="fa-solid fa-user text-xs mr-1"></i> My Profile</a>
                <span class="text-slate-600">|</span>
                <a href="logout" class="text-red-400 hover:text-red-300 transition-colors"><i class="fa-solid fa-power-off text-xs mr-1"></i> Log out</a>
            </div>
        </div>
    </header>

    <div class="max-w-md mx-auto px-5 mt-6">
        
        <!-- Warning Banner -->
        <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-4 mb-6 flex flex-col gap-1">
            <strong class="text-amber-400 text-sm flex items-center gap-2"><i class="fa-solid fa-triangle-exclamation"></i> Physical VIP Card Required</strong>
            <span class="text-slate-400 text-[13px] leading-relaxed">Digital copies or screenshots will NOT be accepted. Please present your physical VIP card at the front desk.</span>
        </div>

        <!-- Premium VIP Point Card -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 border-t-4 border-gold rounded-2xl p-6 shadow-2xl mb-8 relative overflow-hidden">
            <!-- Background Watermark -->
            <i class="fa-solid fa-medal absolute -right-4 -bottom-6 text-9xl text-slate-700/20"></i>
            
            <div class="relative z-10">
                <div class="text-slate-400 text-xs uppercase tracking-widest font-semibold mb-2">Current Balance</div>
                <div class="text-5xl md:text-6xl font-bold text-white mb-4 drop-shadow-md" id="pointCounter" data-target="<?= $member['point_balance'] ?>">0</div>
                
                <div class="text-xs md:text-sm font-medium mb-6 bg-slate-900/70 inline-block px-3 py-2 rounded-lg border border-slate-700">
                    <?php 
                        if (empty($member['next_eligible_date']) || $member['next_eligible_date'] <= date('Y-m-d')) {
                            echo '<span class="text-emerald-400"><i class="fa-solid fa-circle-check mr-1"></i> Eligible to redeem!</span>';
                        } else {
                            echo '<span class="text-red-400"><i class="fa-solid fa-clock mr-1"></i> Next redemption: ' . date('M d, Y', strtotime($member['next_eligible_date'])) . '</span>';
                        }
                    ?>
                </div>

                <!-- Progress Bar Section -->
                <div class="w-full">
                    <div class="text-xs text-slate-300 mb-2 font-medium" style="text-shadow: 1px 1px 2px rgba(0,0,0,0.8);">
                        <?= $progress_msg ?>
                    </div>
                    <div class="h-3 w-full bg-slate-900 rounded-full overflow-hidden border border-slate-700 shadow-inner">
                        <div class="h-full bg-gradient-to-r from-yellow-600 via-gold to-yellow-300 w-0 relative" id="progressBar" data-width="<?= $progress_percentage ?>%">
                            <!-- Kintab effect sa loob ng progress bar -->
                            <div class="absolute top-0 left-0 w-full h-full bg-white/20"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rewards Catalog -->
        <h3 class="text-lg font-bold text-gold mb-4 flex items-center gap-2"><i class="fa-solid fa-gift"></i> Rewards Catalog</h3>
        <div class="flex flex-col gap-3 mb-8">
            <?php foreach ($rewards as $rew): ?>
                <?php $is_affordable = $member['point_balance'] >= $rew['points_required']; ?>
                <div class="flex justify-between items-center bg-slate-800/60 border border-slate-700 rounded-xl p-4 hover:border-gold/30 transition-colors">
                    <span class="text-slate-200 font-medium text-sm"><?= htmlspecialchars($rew['reward_name']) ?></span>
                    <span class="font-bold text-sm <?= $is_affordable ? 'text-emerald-400' : 'text-slate-500' ?>">
                        <?= $rew['points_required'] ?> pts
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Activity -->
        <h3 class="text-lg font-bold text-gold mb-4 flex items-center gap-2"><i class="fa-solid fa-list-check"></i> Recent Activity</h3>
        <div class="bg-slate-800/60 border border-slate-700 rounded-xl overflow-hidden">
            <table class="w-full text-sm text-left">
                <tbody class="divide-y divide-slate-700/50">
                    <?php if(count($transactions) > 0): ?>
                        <?php foreach($transactions as $trx): ?>
                            <tr class="hover:bg-slate-700/30 transition-colors">
                                <td class="p-4">
                                    <div class="font-medium text-slate-200 mb-0.5"><?= htmlspecialchars($trx['activity_type']) ?></div>
                                    <div class="text-xs text-slate-500"><?= date('M d, Y • h:i A', strtotime($trx['transaction_date'])) ?></div>
                                </td>
                                <td class="p-4 text-right font-bold text-emerald-400">+<?= $trx['points_awarded'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="p-6 text-center text-slate-500 text-sm">No activities recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- My Profile Modal -->
    <div id="profileModal" class="hidden fixed inset-0 bg-slate-900/90 backdrop-blur-sm z-[1000] flex items-center justify-center opacity-0 transition-opacity duration-300 px-4">
        <div id="profileContent" class="bg-slate-800 border border-gold/40 rounded-2xl p-6 w-full max-w-sm text-center shadow-[0_15px_40px_rgba(0,0,0,0.6)] transform translate-y-5 transition-transform duration-300">
            
            <div class="w-16 h-16 mx-auto bg-gold/10 border-2 border-gold rounded-full flex items-center justify-center mb-4 shadow-[0_0_15px_rgba(212,175,55,0.2)]">
                <i class="fa-solid fa-user-astronaut text-2xl text-gold"></i>
            </div>
            
            <h3 class="text-xl font-bold text-white mb-1"><?= htmlspecialchars($_SESSION['member_name']) ?></h3>
            <p class="text-emerald-400 text-xs font-semibold uppercase tracking-widest mb-6"><i class="fa-solid fa-circle text-[8px] align-middle mr-1 drop-shadow-md"></i> <?= $member['status'] ?> Member</p>

            <div class="bg-slate-900 p-4 rounded-xl border border-slate-700 text-left mb-6 relative overflow-hidden">
                <!-- Barcode styling decoration -->
                <i class="fa-solid fa-barcode absolute right-[-20px] top-2 text-6xl text-slate-800"></i>
                <span class="block text-slate-500 text-[10px] uppercase tracking-wider mb-1 relative z-10">VIP Card Number</span>
                <?php 
                    $qr = $member['qr_code'];
                    $masked_qr = substr($qr, 0, 6) . '****' . substr($qr, -2);
                ?>
                <strong class="text-white font-mono text-lg tracking-widest relative z-10"><?= $masked_qr ?></strong>
            </div>

            <button onclick="closeProfileModal()" class="w-full bg-gold hover:bg-yellow-500 text-slate-900 font-bold py-3 rounded-xl shadow-lg transition-all active:scale-95">Close Profile</button>
        </div>
    </div>

<script>
        // Gamified Number Counter Animation
        document.addEventListener("DOMContentLoaded", () => {
            const counter = document.getElementById('pointCounter');
            const target = +counter.getAttribute('data-target');
            const duration = 1000;
            const frameRate = 30;
            const totalFrames = Math.round(duration / (1000 / frameRate));
            let currentFrame = 0;

            if (target > 0) {
                const countInterval = setInterval(() => {
                    currentFrame++;
                    const progress = currentFrame / totalFrames;
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

            // Smooth Progress Bar Animation
            setTimeout(() => {
                const pb = document.getElementById('progressBar');
                pb.style.width = pb.getAttribute('data-width');
            }, 400);
        });

        // Tailwind-powered Profile Modal Logic
        function openProfileModal(e) {
            e.preventDefault();
            const modal = document.getElementById('profileModal');
            const content = document.getElementById('profileContent');
            
            modal.classList.remove('hidden');
            
            // Timeout allows the display block to render before triggering opacity transition
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('translate-y-5');
                content.classList.add('translate-y-0');
            }, 10);
        }

        function closeProfileModal() {
            const modal = document.getElementById('profileModal');
            const content = document.getElementById('profileContent');
            
            modal.classList.add('opacity-0');
            content.classList.remove('translate-y-0');
            content.classList.add('translate-y-5');
            
            // Wait for transition to finish before hiding completely
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
    </script>
</body>
</html>