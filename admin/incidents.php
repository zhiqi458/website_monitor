<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/database.php';

$db = getDB();
$incidents = $db->query("
    SELECT i.*, w.name 
    FROM incidents i 
    JOIN websites w ON i.website_id = w.id 
    ORDER BY i.id DESC 
    LIMIT 50
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>故障历史事件 - 监控系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <a href="dashboard.php" class="brand">监控系统后台</a>
        <div class="nav-links">
            <a href="dashboard.php">仪表盘</a>
            <a href="websites.php">网站管理</a>
            <a href="logs.php">监控日志</a>
            <a href="incidents.php">故障事件</a>
            <a href="settings.php">Telegram 设置</a>
            <a href="../logout.php">退出登录</a>
        </div>
    </div>

    <div class="container">
        <h1>故障事故列表 (Incidents)</h1>
        <table>
            <thead>
                <tr>
                    <th>网站</th>
                    <th>触发时状态</th>
                    <th>故障发生时间</th>
                    <th>故障恢复时间</th>
                    <th>响应延时</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($incidents as $inc): ?>
                <tr>
                    <td><?=htmlspecialchars($inc['name'])?></td>
                    <td><span class="badge badge-DOWN"><?=$inc['current_status']?></span></td>
                    <td><?=$inc['created_at']?></td>
                    <td><?=$inc['resolved_at'] ? '<span style="color:green;">'.$inc['resolved_at'].'</span>' : '<b style="color:red;">未恢复</b>'?></td>
                    <td><?=$inc['response_time']?> ms</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>