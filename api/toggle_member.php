<?php
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

if (!empty($data['id']) && !empty($data['current_status'])) {
    $new_status = ($data['current_status'] === 'Active') ? 'Suspended' : 'Active';
    
    try {
        $stmt = $pdo->prepare("UPDATE members SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $new_status, 'id' => $data['id']]);
        
        echo json_encode(['success' => true, 'new_status' => $new_status]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid data.']);
}
?>