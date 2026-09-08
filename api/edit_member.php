<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit();
}

require_once '../config/database.php';
$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['id']) && !empty($data['qr_code']) && !empty($data['first_name']) && !empty($data['last_name'])) {
    try {
        // I-check kung yung QR code ay ginagamit na ng ibang member
        $check = $pdo->prepare("SELECT id FROM members WHERE qr_code = :qr_code AND id != :id");
        $check->execute(['qr_code' => $data['qr_code'], 'id' => $data['id']]);
        
        if ($check->rowCount() > 0) {
            echo json_encode(['success' => false, 'error' => 'QR Code is already in use by another member.']);
            exit();
        }

        $stmt = $pdo->prepare("UPDATE members SET first_name = :fname, last_name = :lname, qr_code = :qr WHERE id = :id");
        $stmt->execute([
            'fname' => trim($data['first_name']),
            'lname' => trim($data['last_name']),
            'qr' => trim($data['qr_code']),
            'id' => $data['id']
        ]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Complete all fields.']);
}
?>