<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/.env');

$environment = $_SERVER['APP_ENV'] ?? 'dev';
$debug = filter_var($_SERVER['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOL);

$kernel = new App\Kernel($environment, $debug);
$pageRequest = Request::create('/smoke');
$pageResponse = $kernel->handle($pageRequest);

if (200 !== $pageResponse->getStatusCode()) {
    throw new RuntimeException(sprintf(
        'The smoke page returned HTTP %d.',
        $pageResponse->getStatusCode(),
    ));
}

$shell = $pageResponse->getContent();

if (!is_string($shell)) {
    throw new RuntimeException('The smoke page has no content.');
}

if (!str_contains($shell, 'data-controller="zhortein--datatable-bundle--datatable"')) {
    throw new RuntimeException('The datatable shell does not expose the Stimulus controller.');
}

if (!str_contains($shell, '/_zhortein/datatable/smoke-users/fragments')) {
    throw new RuntimeException('The datatable shell does not expose the fragments URL.');
}

$kernel->terminate($pageRequest, $pageResponse);

$fragmentsRequest = Request::create('/_zhortein/datatable/smoke-users/fragments');
$response = $kernel->handle($fragmentsRequest);

if (200 !== $response->getStatusCode()) {
    throw new RuntimeException(sprintf(
        'The fragments request returned HTTP %d.',
        $response->getStatusCode(),
    ));
}

$content = $response->getContent();

if (!is_string($content)) {
    throw new RuntimeException('The fragments response has no content.');
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

$kernel->terminate($fragmentsRequest, $response);

fwrite(STDOUT, "Fresh Symfony application smoke test passed.\n");
