<?php
// api/process_point.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']); exit();
}

require_once '../config/database.php';
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['member_id']) && isset($data['activity_type']) && isset($data['points_awarded'])) {
    $member_id = $data['member_id'];
    $activity_type = $data['activity_type'];
    $points_awarded = (int)$data['points_awarded'];
    $admin_id = $_SESSION['admin_id'];
    $current_date = date('Y-m-d');

    try {
        // Kunin ang settings ng activity na ito
        $actStmt = $pdo->prepare("SELECT has_daily_limit FROM activities WHERE activity_name = :activity_name LIMIT 1");
        $actStmt->execute(['activity_name' => $activity_type]);
        $activity = $actStmt->fetch();

        // Check if daily limit applies
        if ($activity && $activity['has_daily_limit'] == 1) {
            $checkStmt = $pdo->prepare("SELECT id FROM point_transactions 
                                        WHERE member_id = :member_id 
                                        AND activity_type = :activity_type 
                                        AND DATE(transaction_date) = :current_date");
            $checkStmt->execute([
                'member_id' => $member_id, 
                'activity_type' => $activity_type,
                'current_date' => $current_date
            ]);
            
            if ($checkStmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'error' => "Limit reached: 1 '$activity_type' point per day allowed."]);
                exit();
            }
        }

        $pdo->beginTransaction();

        $insertStmt = $pdo->prepare("INSERT INTO point_transactions (member_id, admin_id, activity_type, points_awarded) 
                                     VALUES (:member_id, :admin_id, :activity_type, :points)");
        $insertStmt->execute([
            'member_id' => $member_id,
            'admin_id' => $admin_id,
            'activity_type' => $activity_type,
            'points' => $points_awarded
        ]);

        $updateStmt = $pdo->prepare("UPDATE members SET point_balance = point_balance + :points WHERE id = :member_id");
        $updateStmt->execute(['points' => $points_awarded, 'member_id' => $member_id]);

        $balStmt = $pdo->prepare("SELECT point_balance FROM members WHERE id = :member_id");
        $balStmt->execute(['member_id' => $member_id]);
        $new_balance = $balStmt->fetchColumn();

        $pdo->commit();
        echo json_encode(['success' => true, 'new_balance' => (int)$new_balance]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Incomplete data provided.']);
}
?>