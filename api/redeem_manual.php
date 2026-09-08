<?php
// api/redeem_manual.php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Siguraduhing admin ang gumagawa nito
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['member_id']) && isset($data['reward_id'])) {
    $member_id = $data['member_id'];
    $reward_id = $data['reward_id'];
    $admin_id = $_SESSION['admin_id'];

    try {
        $pdo->beginTransaction();

        // 1. Kunin ang detalye ng member
        $stmt = $pdo->prepare("SELECT point_balance, next_eligible_date FROM members WHERE id = :id FOR UPDATE");
        $stmt->execute(['id' => $member_id]);
        $member = $stmt->fetch();

        $today = date('Y-m-d');
        if (!empty($member['next_eligible_date']) && $member['next_eligible_date'] > $today) {
            echo json_encode(['success' => false, 'error' => 'Member is still on cooldown.']);
            $pdo->rollBack();
            exit();
        }

        // 2. Kunin ang detalye ng reward
        $rewStmt = $pdo->prepare("SELECT reward_name, points_required FROM rewards WHERE id = :id AND status = 'Available'");
        $rewStmt->execute(['id' => $reward_id]);
        $reward = $rewStmt->fetch();

        if (!$reward || $member['point_balance'] < $reward['points_required']) {
            echo json_encode(['success' => false, 'error' => 'Insufficient points or reward not available.']);
            $pdo->rollBack();
            exit();
        }

        // 3. I-apply ang Point Forfeiture Rule at 2-Month Cooldown
        $points_before = $member['point_balance'];
        $next_eligible = date('Y-m-d', strtotime('+2 months'));

        // Update member (Zero balance na dahil forfeited ang excess)
        $updStmt = $pdo->prepare("UPDATE members SET point_balance = 0, next_eligible_date = :ned WHERE id = :id");
        $updStmt->execute(['ned' => $next_eligible, 'id' => $member_id]);

        // 4. I-log sa redemption history
        $logStmt = $pdo->prepare("INSERT INTO redemption_history (member_id, reward_id, admin_id, points_before, redeemed_at) VALUES (:mid, :rid, :aid, :pb, NOW())");
        $logStmt->execute([
            'mid' => $member_id,
            'rid' => $reward_id,
            'aid' => $admin_id,
            'pb' => $points_before
        ]);

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Incomplete data.']);
}
?>