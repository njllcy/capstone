<?php
// config.php — database configuration and API token
// Adjust credentials to match your server

$host   = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'medical_kiosk';
$user   = getenv('DB_USER') ?: 'kiosk_user';
$pass   = getenv('DB_PASS') ?: '';

// API token for request authentication (empty string = token check disabled)
define('KIOSK_API_TOKEN', getenv('KIOSK_API_TOKEN') ?: '');

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user, $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}
?>