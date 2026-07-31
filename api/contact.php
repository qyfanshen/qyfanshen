<?php
require_once '../../includes/csrf.php';
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/rate_limit.php';
checkRateLimit('contact', 5, 300);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => '请求方式不正确'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/db.php';

$raw = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

function field(array $payload, string $key, int $maxLength): string {
    $value = isset($payload[$key]) ? (string) $payload[$key] : '';
    $value = trim(strip_tags($value));
    if (mb_strlen($value, 'UTF-8') > $maxLength) {
        $value = mb_substr($value, 0, $maxLength, 'UTF-8');
    }
    return $value;
}

$name = field($payload, 'name', 50);
$company = field($payload, 'company', 120);
$phone = field($payload, 'phone', 30);
$email = field($payload, 'email', 120);
$interest = field($payload, 'interest', 80);
$message = field($payload, 'message', 1000);

if ($name === '' || $phone === '' || $message === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => '请填写姓名、手机号码和需求描述'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => '电子邮箱格式不正确'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $ok = DB::insert([
        'name'       => $name,
        'company'    => $company,
        'phone'      => $phone,
        'email'      => $email,
        'interest'   => $interest,
        'message'    => $message,
        'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500, 'UTF-8'),
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    $cfg = require __DIR__ . '/config.php';
    $msg = !empty($cfg['debug']) ? $e->getMessage() : '服务器错误，请稍后再试';
    echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$ok) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => '提交失败，请稍后再试'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => true, 'message' => '提交成功，我们会尽快联系您！'], JSON_UNESCAPED_UNICODE);
