<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) { echo json_encode(['error' => 'Unauthorized']); exit(); }
require_once '../config/database.php';
$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['name']) && isset($data['points']) && isset($data['limit'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activities (activity_name, points_awarded, has_daily_limit, status) VALUES (?, ?, ?, 'Active')");
        $stmt->execute([trim($data['name']), (int)$data['points'], (int)$data['limit']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error or duplicate name.']);
    }
}
?>