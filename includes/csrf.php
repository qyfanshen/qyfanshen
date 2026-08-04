<?php
/**
 * CSRF 防护中间件
 * 用法: 在需要 POST/PUT/DELETE 的 PHP 文件顶部 require_once 'includes/csrf.php';
 */
session_start();

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'CSRF token validation failed']);
        exit;
    }
    return true;
}

// 自动生成 token
generate_csrf_token();

// 对 POST/PUT/DELETE 请求自动验证
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
    verify_csrf_token();
}
