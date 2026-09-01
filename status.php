<?php


ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once 'includes/database.php';
require_once 'includes/functions.php';

$db = getDB();
$siteId = isset($_GET['site']) ? (int)$_GET['site'] : 0;

$websites = $db->query("SELECT * FROM websites WHERE enabled = 1 ORDER BY id DESC")->fetchAll();

$overallStatus = "🟢 所有系统正常运行中";
foreach ($websites as $w) {
    if ($w['current_status'] === 'DOWN') {
        $overallStatus = "🔴 部分系统遭遇故障异常";
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>服务状态中心 - Status Page</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="public-status-page">
    <div class="public-container">
        <header>
            <h1>服务状态中心</h1>
            <div class="overall-banner"><?=$overallStatus?></div>
        </header>

        <section class="site-list">
            <?php foreach ($websites as $site): 
                $uptime = calculateUptime($site['id'], 90);

                // 查询近 90 天按日归档的运行记录状态
                $historyStmt = $db->prepare("
                    SELECT 
                        DATE(checked_at) as check_date,
                        SUM(CASE WHEN status = 'DOWN' THEN 1 ELSE 0 END) as down_cnt,
                        SUM(CASE WHEN status = 'SLOW' THEN 1 ELSE 0 END) as slow_cnt,
                        COUNT(*) as total_cnt
                    FROM monitoring_logs 
                    WHERE website_id = ? AND checked_at >= DATE_SUB(CURDATE(), INTERVAL 89 DAY)
                    GROUP BY DATE(checked_at)
                ");
                $historyStmt->execute([$site['id']]);
                $historyData = $historyStmt->fetchAll(PDO::FETCH_UNIQUE);
            ?>
            <div class="site-card">
                <div class="site-header">
                    <div>
                        <strong><a href="status.php?site=<?=$site['id']?>"><?=htmlspecialchars($site['name'])?></a></strong>
                        <small class="text-muted">(<?=$site['url']?>)</small>
                    </div>
                    <div>
                        <span class="badge badge-<?=$site['current_status']?>"><?=$site['current_status']?></span>
                        <small><?=$site['response_time']?> ms</small>
                    </div>
                </div>

                <!-- 90 天可视化网格历史 -->
                <div class="history-grid-container">
                    <div class="history-bars">
                        <?php 
                        for ($i = 89; $i >= 0; $i--) {
                            $dateKey = date('Y-m-d', strtotime("-$i days"));
                            if (isset($historyData[$dateKey])) {
                                $day = $historyData[$dateKey];
                                $barClass = 'bar-up';
                                if ($day['down_cnt'] > 0) $barClass = 'bar-down';
                                elseif ($day['slow_cnt'] > 0) $barClass = 'bar-slow';
                                $title = "{$dateKey} | 检查数: {$day['total_cnt']} | 故障: {$day['down_cnt']}";
                            } else {
                                $barClass = 'bar-nodata';
                                $title = "{$dateKey} | 无数据";
                            }
                            echo "<div class='day-bar {$barClass}' title='{$title}'></div>";
                        }
                        ?>
                    </div>
                    <div class="history-bar-footer">
                        <span>90 天前</span>
                        <span>可用率: <strong><?=$uptime?>%</strong></span>
                        <span>今天</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </section>

        <?php if ($siteId > 0): 
            $incidents = $db->prepare("SELECT * FROM incidents WHERE website_id = ? ORDER BY id DESC LIMIT 10");
            $incidents->execute([$siteId]);
        ?>
        <section class="incidents-history">
            <h2>故障事故日志</h2>
            <ul class="incident-list">
                <?php while($inc = $incidents->fetch()): ?>
                <li>
                    <strong>🔴 网站服务中断</strong><br>
                    发生时间: <?=$inc['created_at']?><br>
                    恢复时间: <?=$inc['resolved_at'] ?: '未恢复（故障处理中）'?>
                </li>
                <?php endwhile; ?>
            </ul>
        </section>
        <?php endif; ?>
    </div>
</body>
</html>