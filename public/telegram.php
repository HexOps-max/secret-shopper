<?php
function sendTelegramNotification($message) {
    $token = '8140257213:AAFuF14a8CgLqmiWuTOXe5XyZC-1Jl9ksKs'; // Replace with your correct bot token
    $chat_id = '-4645120212'; // Replace with your correct chat ID
    
    if($token == '8140257213:AAFuF14a8CgLqmiWuTOXe5XyZC-1Jl9ksKs') return; // Skip if not configured

    $url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=" . urlencode($message);
    @file_get_contents($url);
}
?>