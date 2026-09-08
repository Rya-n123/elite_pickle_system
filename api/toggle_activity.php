<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) { echo json_encode(['error' => 'Unauthorized']); exit(); }
require_once '../config/database.php';
$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['id']) && !empty($data['current_status'])) {
    $new_status = ($data['current_status'] === 'Active') ? 'Inactive' : 'Active';
    try {
        $stmt = $pdo->prepare("UPDATE activities SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $data['id']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
}
?>