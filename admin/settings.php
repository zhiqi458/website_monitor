<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/telegram.php';

$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['test_telegram'])) {
        $res = sendTelegramMessage("🧪 <b>测试通知</b>\n这是一条来自网站监控系统的 Telegram 测试消息。");
        $msg = $res ? "✅ 测试消息已成功发送！" : "❌ 发送失败，请检查 Bot Token 和 Chat ID 配置。";
    } else {
        $botToken = trim($_POST['bot_token']);
        $chatId   = trim($_POST['chat_id']);
        $enabled  = isset($_POST['enabled']) ? 1 : 0;

        $stmt = $db->prepare("UPDATE telegram_config SET bot_token = ?, chat_id = ?, enabled = ? WHERE id = 1");
        $stmt->execute([$botToken, $chatId, $enabled]);
        $msg = "设置修改已保存！";
    }
}

$config = $db->query("SELECT * FROM telegram_config WHERE id = 1")->fetch();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>Telegram 设置 - 监控系统</title>
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

    <div class="container" style="max-width:600px;">
        <h1>Telegram Bot 提醒设置</h1>
        <?php if ($msg): ?><div class="alert alert-info"><?=$msg?></div><?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="enabled" value="1" <?=$config['enabled']?'checked':''?>> 开启 Telegram 报警机制
                </label>
            </div>
            <div class="form-group">
                <label>Bot Token</label>
                <input type="text" name="bot_token" value="<?=htmlspecialchars($config['bot_token'])?>" class="form-control" placeholder="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ">
            </div>
            <div class="form-group">
                <label>Chat ID</label>
                <input type="text" name="chat_id" value="<?=htmlspecialchars($config['chat_id'])?>" class="form-control" placeholder="-100123456789">
            </div>
            <button type="submit" class="btn btn-primary">保存配置</button>
            <button type="submit" name="test_telegram" value="1" class="btn" style="background:#2ed573; color:#fff; margin-left:10px;">测试 Telegram 推送</button>
        </form>
    </div>
</body>
</html>