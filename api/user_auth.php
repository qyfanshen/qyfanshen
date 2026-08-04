<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

function userCsrfToken(): string {
    if (empty($_SESSION['user_csrf'])) $_SESSION['user_csrf'] = bin2hex(random_bytes(32));
    return (string)$_SESSION['user_csrf'];
}

function verifyUserRequest(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $requestHost = (string)parse_url('http://' . (string)($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    if ($origin !== '' && strcasecmp((string)parse_url($origin, PHP_URL_HOST), $requestHost) !== 0) {
        throw new RuntimeException('请求来源无效');
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['user_csrf']) || !hash_equals((string)$_SESSION['user_csrf'], $token)) throw new RuntimeException('页面已过期，请刷新后重试');
}

function currentUser(): ?array {
    $id = (int)($_SESSION['user_id'] ?? 0);
    if ($id < 1) return null;
    $user = DB::userById($id);
    if (!$user) unset($_SESSION['user_id']);
    return $user;
}

function requireUser(): array {
    $user = currentUser();
    if (!$user) {
        $target = (string)($_SERVER['REQUEST_URI'] ?? '/account.php');
        header('Location: /?auth=login&redirect=' . rawurlencode($target));
        exit;
    }
    return $user;
}

function loginUser(int $id): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    $_SESSION['user_csrf'] = bin2hex(random_bytes(32));
}

function logoutUser(): void {
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
    $_SESSION['user_csrf'] = bin2hex(random_bytes(32));
}
