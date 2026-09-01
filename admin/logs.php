<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/database.php';

$db = getDB();

// 筛选条件解析
$statusFilter = $_GET['status'] ?? 'ALL';
$timeFilter   = $_GET['time'] ?? 'ALL';
$page         = max(1, (int)($_GET['page'] ?? 1));
$limit        = 20;
$offset       = ($page - 1) * $limit;

$where = [];
$params = [];

if ($statusFilter !== 'ALL') {
    $where[] = "l.status = ?";
    $params[] = $statusFilter;
}

if ($timeFilter === 'TODAY') {
    $where[] = "DATE(l.checked_at) = CURDATE()";
} elseif ($timeFilter === '7DAYS') {
    $where[] = "l.checked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($timeFilter === '30DAYS') {
    $where[] = "l.checked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($timeFilter === '90DAYS') {
    $where[] = "l.checked_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// 获取符合条件的总条数
$countStmt = $db->prepare("SELECT COUNT(*) FROM monitoring_logs l $whereClause");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// 获取分页数据
$sql = "SELECT l.*, w.name FROM monitoring_logs l JOIN websites w ON l.website_id = w.id $whereClause ORDER BY l.checked_at DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>监控日志 - 监控系统</title>
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
        <h1>历史监控日志</h1>
        <form method="GET" class="filter-form">
            <select name="status" class="form-control">
                <option value="ALL" <?=$statusFilter==='ALL'?'selected':''?>>所有状态</option>
                <option value="UP" <?=$statusFilter==='UP'?'selected':''?>>UP</option>
                <option value="DOWN" <?=$statusFilter==='DOWN'?'selected':''?>>DOWN</option>
                <option value="SLOW" <?=$statusFilter==='SLOW'?'selected':''?>>SLOW</option>
            </select>
            <select name="time" class="form-control">
                <option value="ALL" <?=$timeFilter==='ALL'?'selected':''?>>所有时间段</option>
                <option value="TODAY" <?=$timeFilter==='TODAY'?'selected':''?>>今天</option>
                <option value="7DAYS" <?=$timeFilter==='7DAYS'?'selected':''?>>最近 7 天</option>
                <option value="30DAYS" <?=$timeFilter==='30DAYS'?'selected':''?>>最近 30 天</option>
                <option value="90DAYS" <?=$timeFilter==='90DAYS'?'selected':''?>>最近 90 天</option>
            </select>
            <button type="submit" class="btn btn-primary">筛选</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>网站</th>
                    <th>状态</th>
                    <th>响应耗时</th>
                    <th>HTTP Code</th>
                    <th>错误描述</th>
                    <th>检查时间</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($logs as $log): ?>
                <tr>
                    <td><?=htmlspecialchars($log['name'])?></td>
                    <td><span class="badge badge-<?=$log['status']?>"><?=$log['status']?></span></td>
                    <td><?=$log['response_time']?> ms</td>
                    <td><?=$log['http_status_code']?></td>
                    <td><?=htmlspecialchars($log['error_message'] ?: '-')?></td>
                    <td><?=$log['checked_at']?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- 分页控制按钮 -->
        <div class="pagination">
            <?php for($i=1; $i<=$totalPages; $i++): ?>
                <a href="logs.php?page=<?=$i?>&status=<?=$statusFilter?>&time=<?=$timeFilter?>" class="<?=$page===$i?'active':''?>"><?=$i?></a>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>