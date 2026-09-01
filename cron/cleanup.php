<?php
require_once __DIR__ . '/../includes/database.php';

$db = getDB();

// 清理 90 天之前的历史日志 [cite: 12]
$stmt = $db->prepare("DELETE FROM monitoring_logs WHERE checked_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
$stmt->execute();