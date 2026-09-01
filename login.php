<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// 【防死循环关键】如果用户已经登录，直接跳去后台仪表盘，不要停留在登录页
if (isLoggedIn()) {
    header("Location: admin/dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_user'] = $admin['username'];
        header("Location: admin/dashboard.php");
        exit;
    } else {
        $error = "用户名或密码错误。";
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>管理员登录 - 网站监控系统</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-box">
        <h2>系统登录</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" required class="form-control">
            </div>
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" id="pwd" required class="form-control">
                <label><input type="checkbox" onclick="togglePassword()"> 显示密码</label>
            </div>
            <button type="submit" class="btn btn-primary">登录</button>
        </form>
    </div>
    <script>
    function togglePassword() {
        var x = document.getElementById("pwd");
        x.type = x.type === "password" ? "text" : "password";
    }
    </script>
</body>
</html>