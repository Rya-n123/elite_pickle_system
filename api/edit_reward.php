<?php
// api/edit_reward.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit();
}

require_once '../config/database.php';

// CSRF Protection
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    echo json_encode(['success' => false, 'error' => 'Invalid security token. Please refresh the page.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate status against whitelist
$allowedStatuses = ['Available', 'Out of Stock', 'Hidden'];
if (!empty($data['id']) && !empty($data['reward_name']) && !empty($data['points_required']) && !empty($data['status']) && in_array($data['status'], $allowedStatuses)) {
    try {
        $stmt = $pdo->prepare("UPDATE rewards SET reward_name = :name, points_required = :points, status = :status WHERE id = :id");
        $stmt->execute([
            'name' => trim($data['reward_name']),
            'points' => (int)$data['points_required'],
            'status' => $data['status'],
            'id' => $data['id']
        ]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Please fill in all fields.']);
}
?>