<?php
require_once '../../includes/csrf.php';
declare(strict_types=1);

function checkRateLimit(string $key, int $max = 10, int $win = 60): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $dir = sys_get_temp_dir() . '/rate_limit';
    @mkdir($dir, 0700, true);
    $file = $dir . '/' . md5($ip . '_' . $key);
    $now = time();
    $handle = @fopen($file, 'c+');
    if ($handle === false) return;

    flock($handle, LOCK_EX);
    $raw = stream_get_contents($handle);
    $data = json_decode($raw ?: '', true) ?: ['count' => 0, 'reset' => $now + $win];
    if ($now >= (int) $data['reset']) $data = ['count' => 0, 'reset' => $now + $win];
    $data['count']++;
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($data));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    if ($data['count'] > $max) {
        http_response_code(429);
        header('Retry-After: ' . max(1, (int) $data['reset'] - $now));
        die(json_encode(['error' => '请求过于频繁'], JSON_UNESCAPED_UNICODE));
    }
}
