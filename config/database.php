<?php
// config/database.php

// Strict timezone para iwas aberya sa date calculations (tulad ng 2-month lockout)
date_default_timezone_set('Asia/Manila');

// ============================================
// SESSION SECURITY HARDENING
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    // I-set ang secure cookie parameters BAGO mag-session_start()
    session_set_cookie_params([
        'lifetime' => 0,           // Session cookie lang (mawawala kapag nag-close ng browser)
        'path'     => '/',
        'domain'   => '',          // Auto-detect ang domain
        'secure'   => isset($_SERVER['HTTPS']),  // Auto-detect: true on HTTPS (Hostinger), false on HTTP (XAMPP)
        'httponly'  => true,        // Hindi accessible ng JavaScript (anti-XSS)
        'samesite' => 'Lax'        // Anti-CSRF basic protection
    ]);
}

// ============================================
// DATABASE CREDENTIALS
// Palitan ito ng Hostinger credentials mo bago i-deploy!
// ============================================
$host = 'localhost';
$dbname = 'elite_pickle_db';
$username = 'root';
$password = '';

try {
    // Setup DSN (Data Source Name)
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    
    // PDO Security & Best Practices Options
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Mag-throw ng errors bilang exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // I-return ang data bilang associative array
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Patayin ang emulated prepares para sa strict SQL injection prevention
    ];

    // Initialize PDO connection
    $pdo = new PDO($dsn, $username, $password, $options);

    // I-sync ang MySQL timezone sa Philippine Time (+08:00) para tugma sa scanner
    $pdo->exec("SET time_zone = '+08:00'");

} catch (PDOException $e) {
    // I-log ang error nang hindi ipinapakita ang sensitibong database info sa user
    error_log("Database Connection Failed: " . $e->getMessage());
    http_response_code(500);
    die("System error: Unable to connect to the database. Please try again later.");
}

// ============================================
// CSRF TOKEN HELPER FUNCTIONS
// ============================================
function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================
// RATE LIMITING HELPER (File-Based)
// ============================================
function checkRateLimit($identifier, $maxAttempts = 5, $windowSeconds = 900) {
    $rateLimitDir = sys_get_temp_dir() . '/elite_ratelimit/';
    if (!is_dir($rateLimitDir)) {
        @mkdir($rateLimitDir, 0700, true);
    }
    
    $file = $rateLimitDir . md5($identifier) . '.json';
    $attempts = [];
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) {
            // Tanggalin ang expired attempts (mas matanda sa window)
            $cutoff = time() - $windowSeconds;
            $attempts = array_filter($data, function($timestamp) use ($cutoff) {
                return $timestamp > $cutoff;
            });
        }
    }
    
    if (count($attempts) >= $maxAttempts) {
        return false; // Rate limited!
    }
    
    // I-record ang bagong attempt
    $attempts[] = time();
    file_put_contents($file, json_encode(array_values($attempts)), LOCK_EX);
    
    return true; // OK pa
}

// HTML escape helper para sa inline JS attributes
function jsAttr($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>