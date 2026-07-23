<?php

declare(strict_types=1);

if (3 !== $argc) {
    throw new InvalidArgumentException('Expected the shell and fragments response paths.');
}

$shell = file_get_contents($argv[1]);

if (!is_string($shell)) {
    throw new RuntimeException('The smoke page response could not be read.');
}

if (!str_contains($shell, 'data-controller="zhortein--datatable-bundle--datatable"')) {
    throw new RuntimeException('The datatable shell does not expose the Stimulus controller.');
}

if (!str_contains($shell, '/_zhortein/datatable/smoke-users/fragments')) {
    throw new RuntimeException('The datatable shell does not expose the fragments URL.');
}

$content = file_get_contents($argv[2]);

if (!is_string($content)) {
    throw new RuntimeException('The fragments response could not be read.');
}

$payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

if (2 !== ($payload['totalItems'] ?? null)) {
    throw new RuntimeException('The fragments response has an invalid total item count.');
}

$body = $payload['body'] ?? null;

if (
    !is_string($body)
    || !str_contains($body, 'alice@example.test')
    || !str_contains($body, 'bob@example.test')
) {
    throw new RuntimeException('The fragments response does not contain the expected rows.');
}

fwrite(STDOUT, "Fresh Symfony application smoke test passed.\n");
