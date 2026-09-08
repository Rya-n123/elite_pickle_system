<?php
// api/fetch_activities.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']); exit();
}

require_once '../config/database.php';

try {
    $stmt = $pdo->query("SELECT activity_name, points_awarded, has_daily_limit FROM activities WHERE status = 'Active'");
    $activities = $stmt->fetchAll();
    echo json_encode(['activities' => $activities]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
?>