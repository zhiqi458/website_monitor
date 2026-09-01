<?php
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// 计算 90 天可用率 
function calculateUptime($websiteId, $days = 90) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'UP' THEN 1 ELSE 0 END) as up_count
        FROM monitoring_logs 
        WHERE website_id = ? AND checked_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$websiteId, $days]);
    $res = $stmt->fetch();

    if (!$res || $res['total'] == 0) return 100.00; // 默认 100%

    return round(($res['up_count'] / $res['total']) * 100, 2);
}