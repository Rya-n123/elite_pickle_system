<?php
// api/process_point.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']); exit();
}

require_once '../config/database.php';

// CSRF Protection
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    echo json_encode(['success' => false, 'error' => 'Invalid security token. Please refresh the page.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['member_id']) && isset($data['activity_type']) && isset($data['points_awarded'])) {
    $member_id = (int)$data['member_id'];
    $activity_type = trim($data['activity_type']);
    $points_awarded = (int)$data['points_awarded'];
    
    // Validate: points must be positive
    if ($points_awarded <= 0) {
        echo json_encode(['success' => false, 'error' => 'Points must be a positive number.']);
        exit();
    }
    
    // Validate: activity must exist and be active
    $actValidateStmt = $pdo->prepare("SELECT points_awarded FROM activities WHERE activity_name = :name AND status = 'Active' LIMIT 1");
    $actValidateStmt->execute(['name' => $activity_type]);
    $validActivity = $actValidateStmt->fetch();
    if (!$validActivity) {
        echo json_encode(['success' => false, 'error' => 'Invalid or inactive activity.']);
        exit();
    }
    // Override points with the database value (prevent tampering)
    $points_awarded = (int)$validActivity['points_awarded'];
    $admin_id = $_SESSION['admin_id'];
    $current_date = date('Y-m-d');

    try {
        // Kunin ang settings ng activity na ito
        $actStmt = $pdo->prepare("SELECT has_daily_limit FROM activities WHERE activity_name = :activity_name LIMIT 1");
        $actStmt->execute(['activity_name' => $activity_type]);
        $activity = $actStmt->fetch();

        $pdo->beginTransaction();
        
        // Check if daily limit applies (INSIDE transaction to prevent race condition)
        if ($activity && $activity['has_daily_limit'] == 1) {
            $checkStmt = $pdo->prepare("SELECT id FROM point_transactions 
                                        WHERE member_id = :member_id 
                                        AND activity_type = :activity_type 
                                        AND DATE(transaction_date) = :current_date
                                        FOR UPDATE");
            $checkStmt->execute([
                'member_id' => $member_id, 
                'activity_type' => $activity_type,
                'current_date' => $current_date
            ]);
            
            if ($checkStmt->rowCount() > 0) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => "Limit reached: 1 '$activity_type' point per day allowed."]);
                exit();
            }
        }

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