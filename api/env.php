<?php
require_once '../../includes/csrf.php';
declare(strict_types=1);

function envValue(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value !== false) return $value;

    static $values = null;
    if ($values === null) {
        $values = [];
        $envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                [$name, $envValue] = array_map('trim', explode('=', $line, 2));
                $values[$name] = trim($envValue, "\"'");
            }
        }
    }
    return array_key_exists($key, $values) ? $values[$key] : $default;
}

function envBool(string $key, bool $default = false): bool
{
    $value = envValue($key);
    return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
}
