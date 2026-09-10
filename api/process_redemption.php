<?php
// api/process_redemption.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit();
}

require_once '../config/database.php';

// CSRF Protection
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    echo json_encode(['success' => false, 'error' => 'Invalid security token. Please refresh the page.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['member_id']) && isset($data['reward_id'])) {
    $member_id = $data['member_id'];
    $reward_id = $data['reward_id'];
    $admin_id = $_SESSION['admin_id'];

    try {
        $pdo->beginTransaction();

        // 1. Kunin ang current data ng member
        $memStmt = $pdo->prepare("SELECT point_balance, next_eligible_date FROM members WHERE id = :member_id FOR UPDATE");
        $memStmt->execute(['member_id' => $member_id]);
        $member = $memStmt->fetch();

        // 2. Kunin ang requirement ng reward
        $rewStmt = $pdo->prepare("SELECT points_required FROM rewards WHERE id = :reward_id");
        $rewStmt->execute(['reward_id' => $reward_id]);
        $reward = $rewStmt->fetch();

        $current_date = date('Y-m-d');

        // Validation Checks
        if (!$member || !$reward) {
            throw new Exception("Invalid member or reward data.");
        }
        if ($member['point_balance'] < $reward['points_required']) {
            throw new Exception("Insufficient points for this reward.");
        }
        if (!empty($member['next_eligible_date']) && $current_date < $member['next_eligible_date']) {
            throw new Exception("Member is still within the 2-month redemption lockout period.");
        }

        // 3. I-save sa Redemption History (Audit Trail)
        $histStmt = $pdo->prepare("INSERT INTO redemption_history (member_id, admin_id, reward_id, points_before, points_after) 
                                   VALUES (:member_id, :admin_id, :reward_id, :points_before, 0)");
        $histStmt->execute([
            'member_id' => $member_id,
            'admin_id' => $admin_id,
            'reward_id' => $reward_id,
            'points_before' => $member['point_balance']
        ]);

        // 4. RULE 5 & 10: The Zero-Reset & Lockout Update
        // Strict logic: Laging 0 ang points after. Lockout is Exactly Current Date + 2 Months.
        $updateStmt = $pdo->prepare("UPDATE members 
                                     SET point_balance = 0, 
                                         last_redemption = CURRENT_DATE, 
                                         next_eligible_date = DATE_ADD(CURRENT_DATE, INTERVAL 2 MONTH) 
                                     WHERE id = :member_id");
        $updateStmt->execute(['member_id' => $member_id]);

        // Kunin ang exact future date para ipakita sa sweetalert
        $dateStmt = $pdo->prepare("SELECT next_eligible_date FROM members WHERE id = :member_id");
        $dateStmt->execute(['member_id' => $member_id]);
        $new_date = $dateStmt->fetchColumn();

        $pdo->commit();

        $formatted_date = date('F d, Y', strtotime($new_date));
        echo json_encode(['success' => true, 'next_eligible' => $formatted_date]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Incomplete data provided.']);
}
?>