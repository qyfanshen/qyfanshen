<?php
require_once '../../includes/csrf.php';
declare(strict_types=1);

require_once dirname(__DIR__) . '/api/env.php';

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();
define('SESSION_TIMEOUT', 7200);
function isLoggedIn() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) return false;
    if (isset($_SESSION['admin_login_time'])) {
        if (time() - $_SESSION['admin_login_time'] > SESSION_TIMEOUT) { logout(); return false; }
    }
    $_SESSION['admin_login_time'] = time(); return true;
}
function requireLogin() { if (!isLoggedIn()) { header('Location: login.php'); exit; } }
function verifyPassword($input) {
    $adminPassword = envValue('ADMIN_PASSWORD', '');
    if (empty($adminPassword)) return false;
    if (str_starts_with($adminPassword, '$2y$') || str_starts_with($adminPassword, '$argon2')) {
        return password_verify($input, $adminPassword);
    }
    return hash_equals($adminPassword, $input);
}
function doLogin($password) {
    if (verifyPassword($password)) {
        $_SESSION['admin_logged_in'] = true; $_SESSION['admin_login_time'] = time();
        session_regenerate_id(true); return true;
    }
    return false;
}
function logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
function csrfToken() {
    if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verifyCsrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) { http_response_code(403); die(json_encode(['error' => 'CSRF token 验证失败'])); }
    }
}
