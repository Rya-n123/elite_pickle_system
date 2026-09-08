<?php
// api/fetch_member.php
session_start();

// Siguraduhing JSON ang ire-return ng file na ito
header('Content-Type: application/json');

// Security Check: Bawal i-access ito kung hindi naka-login
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit();
}

require_once '../config/database.php';

if (isset($_GET['qr_code'])) {
    $qr_code = trim($_GET['qr_code']);
    
    try {
        // Hanapin ang member gamit ang na-scan na QR code
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, point_balance, next_eligible_date, status FROM members WHERE qr_code = :qr_code LIMIT 1");
        $stmt->execute(['qr_code' => $qr_code]);
        $member = $stmt->fetch();
        
        if ($member) {
            // Business Logic: I-check ang 2-month lockout restriction
            $is_eligible = true;
            $current_date = date('Y-m-d');
            
            if (!empty($member['next_eligible_date']) && $current_date < $member['next_eligible_date']) {
                $is_eligible = false;
            }

            // I-format ang date para magandang basahin sa frontend
            $formatted_date = $member['next_eligible_date'] ? date('F d, Y', strtotime($member['next_eligible_date'])) : 'Eligible Now';

            // I-return ang data bilang JSON para ma-process ng JavaScript
            echo json_encode([
                'success' => true,
                'member' => [
                    'id' => $member['id'],
                    'first_name' => $member['first_name'],
                    'last_name' => $member['last_name'],
                    'point_balance' => (int)$member['point_balance'],
                    'status' => $member['status'],
                    'is_eligible' => $is_eligible,
                    'next_eligible_date' => $formatted_date
                ]
            ]);
        } else {
            // Walang nahanap na record
            echo json_encode(['success' => false, 'error' => 'Member not found.']);
        }
    } catch (PDOException $e) {
        // Iwasang mag-leak ng SQL errors sa frontend
        error_log("Fetch Member Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'System error. Please try again.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No QR code provided.']);
}
?>