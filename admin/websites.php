<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();

// 处理删除逻辑
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        $stmt = $db->prepare("DELETE FROM websites WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        header("Location: websites.php?msg=deleted");
        exit;
    }
}

// 处理启停切换逻辑
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    if (verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        $stmt = $db->prepare("UPDATE websites SET enabled = NOT enabled WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        header("Location: websites.php");
        exit;
    }
}

$websites = $db->query("SELECT * FROM websites ORDER BY id DESC")->fetchAll();
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>网站管理 - 监控系统</title>
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
        <div class="header-action">
            <h1>网站管理</h1>
            <a href="website-add.php" class="btn btn-primary">+ 添加新网站</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>名称</th>
                    <th>URL</th>
                    <th>频率</th>
                    <th>慢速阈值</th>
                    <th>监控状态</th>
                    <th>当前运行</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($websites as $site): ?>
                <tr>
                    <td><strong><?=htmlspecialchars($site['name'])?></strong></td>
                    <td><a href="<?=htmlspecialchars($site['url'])?>" target="_blank"><?=htmlspecialchars($site['url'])?></a></td>
                    <td><?=$site['monitoring_interval']?> 分钟</td>
                    <td><?=$site['slow_threshold']?> ms</td>
                    <td>
                        <a href="websites.php?action=toggle&id=<?=$site['id']?>&csrf_token=<?=$csrfToken?>">
                            <?=$site['enabled'] ? '🟢 已启用' : '⚪ 已禁用'?>
                        </a>
                    </td>
                    <td><span class="badge badge-<?=$site['current_status']?>"><?=$site['current_status']?></span></td>
                    <td>
                        <a href="website-edit.php?id=<?=$site['id']?>">编辑</a> | 
                        <a href="websites.php?action=delete&id=<?=$site['id']?>&csrf_token=<?=$csrfToken?>" onclick="return confirm('确定删除此网站及其监控记录？')">删除</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>   
        </table>
    </div>
</body>
</html>