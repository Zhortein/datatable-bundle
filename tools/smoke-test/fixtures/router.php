<?php

declare(strict_types=1);

foreach (['APP_ENV', 'APP_DEBUG', 'DATABASE_URL'] as $environmentVariable) {
    $value = getenv($environmentVariable);

    if (!is_string($value) || '' === $value) {
        continue;
    }

    $_SERVER[$environmentVariable] = $value;
    $_ENV[$environmentVariable] = $value;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (is_string($path) && '/' !== $path && is_file(__DIR__.$path)) {
    return false;
}

require __DIR__.'/index.php';
