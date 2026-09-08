<?php
// api/search_member.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../config/database.php';

if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $search = trim($_GET['query']);
    $likeQuery = "%$search%";
    
    // Check if the query is a number (for ID searching)
    $idQuery = is_numeric($search) ? (int)$search : 0;

    try {
        $stmt = $pdo->prepare("
            SELECT id, qr_code, first_name, last_name, point_balance, status 
            FROM members 
            WHERE first_name LIKE :q1 
               OR last_name LIKE :q2 
               OR qr_code LIKE :q3 
               OR id = :q4
            LIMIT 10
        ");
        
        $stmt->execute([
            'q1' => $likeQuery,
            'q2' => $likeQuery,
            'q3' => $likeQuery,
            'q4' => $idQuery
        ]);
        
        $results = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'results' => $results]);
    } catch (PDOException $e) {
        error_log("Search Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Empty query']);
}
?>