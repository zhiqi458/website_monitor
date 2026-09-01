<?php
require_once __DIR__ . '/database.php';

function sendTelegramMessage($message) {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM telegram_config WHERE id = 1");
    $config = $stmt->fetch();

    // 优先读取数据库配置，若无则取 .env 中的缺省配置
    $botToken = !empty($config['bot_token']) ? $config['bot_token'] : getenv('TELEGRAM_BOT_TOKEN');
    $chatId = !empty($config['chat_id']) ? $config['chat_id'] : getenv('TELEGRAM_CHAT_ID');
    $enabled = isset($config['enabled']) ? $config['enabled'] : getenv('TELEGRAM_ENABLED');

    if (!$enabled || empty($botToken) || empty($chatId)) {
        return false;
    }

    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}