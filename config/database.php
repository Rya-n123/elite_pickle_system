<?php
// config/database.php

// Strict timezone para iwas aberya sa date calculations (tulad ng 2-month lockout)
date_default_timezone_set('Asia/Manila');

// Local XAMPP Database credentials
$host = 'localhost';
$dbname = 'elite_pickle_db';
$username = 'root'; // XAMPP default
$password = '';     // XAMPP default

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

} catch (PDOException $e) {
    // I-log ang error nang hindi ipinapakita ang sensitibong database info sa user
    error_log("Database Connection Failed: " . $e->getMessage());
    die("System error: Unable to connect to the database. Please try again later.");
}
?>