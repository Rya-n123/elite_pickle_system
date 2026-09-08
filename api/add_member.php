<?php
// api/add_member.php
session_start();
header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit();
}

require_once '../config/database.php';
$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['qr_code']) && !empty($data['first_name']) && !empty($data['last_name'])) {
    
    // I-sanitize ang inputs
    $qr_code = trim($data['qr_code']);
    $first_name = trim($data['first_name']);
    $last_name = trim($data['last_name']);

    try {
        // I-check muna kung may kaparehas na QR code na sa database
        $checkStmt = $pdo->prepare("SELECT id FROM members WHERE qr_code = :qr_code");
        $checkStmt->execute(['qr_code' => $qr_code]);
        
        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'error' => 'QR Code is already registered to another member.']);
            exit();
        }

        // I-insert ang bagong member (0 points default)
        $insertStmt = $pdo->prepare("INSERT INTO members (qr_code, first_name, last_name, point_balance, status) 
                                     VALUES (:qr_code, :first_name, :last_name, 0, 'Active')");
        $insertStmt->execute([
            'qr_code' => $qr_code,
            'first_name' => $first_name,
            'last_name' => $last_name
        ]);

        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        error_log("Add Member Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Please fill in all fields.']);
}
?>