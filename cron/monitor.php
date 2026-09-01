<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/telegram.php';

$db = getDB();

// 获取所有已启用的网站
$stmt = $db->prepare("SELECT * FROM websites WHERE enabled = 1");
$stmt->execute();
$websites = $stmt->fetchAll();

$now = new DateTime();

foreach ($websites as $site) {
    // 检查是否到了执行间隔时间
    if ($site['last_checked']) {
        $lastChecked = new DateTime($site['last_checked']);
        $diffMinutes = ($now->getTimestamp() - $lastChecked->getTimestamp()) / 60;
        if ($diffMinutes < $site['monitoring_interval']) {
            continue; // 间隔未到，跳过
        }
    }

    // 执行 cURL HTTP 请求检查 [cite: 5]
    $ch = curl_init($site['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $startTime = microtime(true);
    $response = curl_exec($ch);
    $endTime = microtime(true);

    $responseTime = round(($endTime - $startTime) * 1000); // 毫秒
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // 状态判定逻辑 [cite: 5, 6, 7]
    echo $httpCode;
    $status = 'DOWN';
    if ($httpCode >= 200 && $httpCode < 400) {
        if ($responseTime > $site['slow_threshold']) {
            $status = 'SLOW';
        } else {
            $status = 'UP';
        }
    }

    // 1. 写入监控日志 [cite: 5]
    $logStmt = $db->prepare("INSERT INTO monitoring_logs (website_id, status, response_time, http_status_code, error_message, checked_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $logStmt->execute([$site['id'], $status, $responseTime, $httpCode, $curlError]);

    // 2. 判定状态是否变化并处理 Telegram 报警 [cite: 5, 8, 10, 22]
    $prevStatus = $site['current_status'];
    if ($prevStatus !== $status) {
        // 更新事故 Incident 表 [cite: 22]
        if ($status === 'DOWN') {
            $incStmt = $db->prepare("INSERT INTO incidents (website_id, previous_status, current_status, response_time, created_at) VALUES (?, ?, ?, ?, NOW())");
            $incStmt->execute([$site['id'], $prevStatus, $status, $responseTime]);
        } elseif ($prevStatus === 'DOWN' && $status === 'UP') {
            $incStmt = $db->prepare("UPDATE incidents SET resolved_at = NOW() WHERE website_id = ? AND resolved_at IS NULL");
            $incStmt->execute([$site['id']]);
        }

        // 发送 Telegram 消息机制 [cite: 8, 10]
        $formattedTime = date('Y-m-d H:i:s');
        if ($status === 'DOWN') {
            $msg = "🚨 <b>WEBSITE DOWN</b>\n\n";
            $msg .= "<b>Website:</b> {$site['name']}\n";
            $msg .= "<b>URL:</b> {$site['url']}\n";
            $msg .= "<b>Status:</b> DOWN\n";
            $msg .= "<b>Time:</b> {$formattedTime}";
            sendTelegramMessage($msg);
        } elseif ($prevStatus === 'DOWN' && $status === 'UP') {
            $msg = "✅ <b>WEBSITE RECOVERED</b>\n\n";
            $msg .= "<b>Website:</b> {$site['name']}\n";
            $msg .= "<b>URL:</b> {$site['url']}\n";
            $msg .= "<b>Response Time:</b> {$responseTime} ms\n";
            $msg .= "<b>Time:</b> {$formattedTime}";
            sendTelegramMessage($msg);
        } elseif ($status === 'SLOW') {
            $msg = "⚠️ <b>SLOW RESPONSE</b>\n\n";
            $msg .= "<b>Website:</b> {$site['name']}\n";
            $msg .= "<b>URL:</b> {$site['url']}\n";
            $msg .= "<b>Response Time:</b> {$responseTime} ms\n";
            $msg .= "<b>Threshold:</b> {$site['slow_threshold']} ms";
            sendTelegramMessage($msg);
        }
    }

    // 3. 更新主表状态
    $updStmt = $db->prepare("UPDATE websites SET current_status = ?, last_checked = NOW(), response_time = ?, http_status_code = ? WHERE id = ?");
    $updStmt->execute([$status, $responseTime, $httpCode, $site['id']]);
}