<?php
require_once __DIR__ . '/../config/config.php';

function requireLogin() {
    if (!isLoggedIn()) {
        // 使用根路径，防止在根目录与 admin/ 目录下相对路径跳转出错
        header("Location: /website-monitor/login.php");
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}