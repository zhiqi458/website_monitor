<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF 令牌验证失败";
    } else {
        $name     = sanitizeInput($_POST['name']);
        $url      = sanitizeInput($_POST['url']);
        $interval = (int)$_POST['monitoring_interval'];
        $threshold= (int)$_POST['slow_threshold'];

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $error = "请输入有效的网址 URL";
        } else {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO websites (name, url, monitoring_interval, slow_threshold) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $url, $interval, $threshold]);
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
    <title>添加网站 - 监控系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container" style="max-width: 600px;">
        <h1>添加监控网站</h1>
        <?php if ($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?=$csrfToken?>">
            <div class="form-group">
                <label>网站名称</label>
                <input type="text" name="name" placeholder="例如: Google" required class="form-control">
            </div>
            <div class="form-group">
                <label>目标 URL</label>
                <input type="url" name="url" placeholder="https://www.google.com" required class="form-control">
            </div>
            <div class="form-group">
                <label>监控间隔 (分钟)</label>
                <input type="number" name="monitoring_interval" value="5" min="1" required class="form-control">
            </div>
            <div class="form-group">
                <label>响应过慢判定阈值 (毫秒 ms)</label>
                <input type="number" name="slow_threshold" value="3000" min="100" required class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">保存网站</button>
            <a href="websites.php" style="margin-left:10px;">返回</a>
        </form>
    </div>
</body>
</html>