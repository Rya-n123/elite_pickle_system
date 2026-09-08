<?php
// api/add_reward.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit();
}

require_once '../config/database.php';
$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['reward_name']) && !empty($data['points_required']) && !empty($data['status'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO rewards (reward_name, points_required, status) VALUES (:name, :points, :status)");
        $stmt->execute([
            'name' => trim($data['reward_name']),
            'points' => (int)$data['points_required'],
            'status' => $data['status']
        ]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Please fill in all fields.']);
}
?>