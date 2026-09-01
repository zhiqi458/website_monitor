<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/database.php';

$db = getDB();

// 聚合数据统计 
$totalSites = $db->query("SELECT COUNT(*) FROM websites")->fetchColumn();
$upSites    = $db->query("SELECT COUNT(*) FROM websites WHERE current_status = 'UP' AND enabled = 1")->fetchColumn();
$downSites  = $db->query("SELECT COUNT(*) FROM websites WHERE current_status = 'DOWN' AND enabled = 1")->fetchColumn();
$slowSites  = $db->query("SELECT COUNT(*) FROM websites WHERE current_status = 'SLOW' AND enabled = 1")->fetchColumn();

// 最新监测日志 
$recentLogs = $db->query("
    SELECT l.*, w.name 
    FROM monitoring_logs l 
    JOIN websites w ON l.website_id = w.id 
    ORDER BY l.checked_at DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>控制面板 - 网站监控</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <a href="dashboard.php" class="brand">监控系统后台</a>
        <div class="nav-links">
            <a href="websites.php">网站管理</a>
            <a href="logs.php">监控日志</a>
            <a href="incidents.php">故障事件</a>
            <a href="settings.php">Telegram 设置</a>
            <a href="../logout.php">退出登录</a>
        </div>
    </div>

    <div class="container">
        <h1>仪表盘概览</h1>
        <div class="cards-grid">
            <div class="card"><h3>总监控网站</h3><p class="stat"><?=$totalSites?></p></div>
            <div class="card card-up"><h3>🟢 正常 (UP)</h3><p class="stat"><?=$upSites?></p></div>
            <div class="card card-down"><h3>🔴 离线 (DOWN)</h3><p class="stat"><?=$downSites?></p></div>
            <div class="card card-slow"><h3>🟡 缓慢 (SLOW)</h3><p class="stat"><?=$slowSites?></p></div>
        </div>

        <h2>近期活动日志</h2>
        <table>
            <thead>
                <tr>
                    <th>网站</th>
                    <th>状态</th>
                    <th>响应时间</th>
                    <th>HTTP状态码</th>
                    <th>时间</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recentLogs as $log): ?>
                <tr>
                    <td><?=htmlspecialchars($log['name'])?></td>
                    <td><span class="badge badge-<?=$log['status']?>"><?=$log['status']?></span></td>
                    <td><?=$log['response_time']?> ms</td>
                    <td><?=$log['http_status_code']?></td>
                    <td><?=$log['checked_at']?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>