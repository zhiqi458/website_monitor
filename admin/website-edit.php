<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM websites WHERE id = ?");
$stmt->execute([$id]);
$site = $stmt->fetch();

if (!$site) {
    header("Location: websites.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF 验证失败";
    } else {
        $name      = sanitizeInput($_POST['name']);
        $url       = sanitizeInput($_POST['url']);
        $interval  = (int)$_POST['monitoring_interval'];
        $threshold = (int)$_POST['slow_threshold'];

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $error = "请输入有效的网址 URL";
        } else {
            $stmt = $db->prepare("UPDATE websites SET name = ?, url = ?, monitoring_interval = ?, slow_threshold = ? WHERE id = ?");
            $stmt->execute([$name, $url, $interval, $threshold, $id]);
            header("Location: websites.php");
            exit;
        }
    }
}
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>编辑网站 - 监控系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container" style="max-width: 600px;">
        <h1>编辑网站监控项</h1>
        <?php if ($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?=$csrfToken?>">
            <div class="form-group">
                <label>网站名称</label>
                <input type="text" name="name" value="<?=htmlspecialchars($site['name'])?>" required class="form-control">
            </div>
            <div class="form-group">
                <label>目标 URL</label>
                <input type="url" name="url" value="<?=htmlspecialchars($site['url'])?>" required class="form-control">
            </div>
            <div class="form-group">
                <label>监控间隔 (分钟)</label>
                <input type="number" name="monitoring_interval" value="<?=$site['monitoring_interval']?>" min="1" required class="form-control">
            </div>
            <div class="form-group">
                <label>响应过慢判定阈值 (毫秒 ms)</label>
                <input type="number" name="slow_threshold" value="<?=$site['slow_threshold']?>" min="100" required class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">更新配置</button>
            <a href="websites.php" style="margin-left:10px;">取消</a>
        </form>
    </div>
</body>
</html>