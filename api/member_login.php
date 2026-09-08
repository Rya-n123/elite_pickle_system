<?php
// api/member_login.php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['qr_code']) && isset($data['last_name'])) {
    $qr_code = trim($data['qr_code']);
    $last_name = trim($data['last_name']);

    try {
        // Verify gamit ang Card Number (QR Code) at Last Name
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, status FROM members WHERE qr_code = :qr_code AND last_name = :last_name LIMIT 1");
        $stmt->execute(['qr_code' => $qr_code, 'last_name' => $last_name]);
        $member = $stmt->fetch();

        if ($member) {
            if ($member['status'] === 'Suspended') {
                echo json_encode(['success' => false, 'error' => 'Your account is suspended. Please contact staff.']);
                exit();
            }
            $_SESSION['member_id'] = $member['id'];
            $_SESSION['member_name'] = $member['first_name'] . ' ' . $member['last_name'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid VIP Card Number or Last Name.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Please fill in all fields.']);
}
?>