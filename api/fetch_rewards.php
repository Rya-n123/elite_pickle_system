<?php
// api/fetch_rewards.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../config/database.php';

try {
    $stmt = $pdo->query("SELECT id, reward_name, points_required FROM rewards WHERE status = 'Available' ORDER BY points_required ASC");
    $rewards = $stmt->fetchAll();
    echo json_encode(['rewards' => $rewards]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
?>