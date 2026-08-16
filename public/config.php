<?php
$host     = getenv('DB_HOST') ?: 'localhost';
$port     = getenv('DB_PORT') ?: '3306';
$db       = getenv('DB_NAME') ?: 'shop';
$user     = getenv('DB_USER') ?: 'root';
$pass     = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Telegram Notification Function
function sendTelegramNotification($message) {
    $token = '8140257213:AAFuF14a8CgLqmiWuTOXe5XyZC-1Jl9ksKs'; // Replace with your correct bot token
    $chat_id = '-4645120212';     // Replace with your correct chat ID
    
    if($botToken && $chatId) {
        $url = "https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chatId&text=" . urlencode($message) . "&parse_mode=Markdown";
        @file_get_contents($url);
    }
}
?>