<?php

declare(strict_types=1);

if (4 !== $argc) {
    throw new InvalidArgumentException('Expected the shell, fragments and CSV response paths.');
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

foreach ([
    'data-zhortein--datatable-bundle--datatable-page-size-value="15"',
    'data-zhortein--datatable-bundle--datatable-search-builder-value="true"',
    'data-zhortein--datatable-bundle--datatable-target="searchInput"',
    'data-zhortein--datatable-bundle--datatable-target="searchBuilder"',
    'table-bordered',
    'table-sm',
] as $expectedShellContent) {
    if (!str_contains($shell, $expectedShellContent)) {
        throw new RuntimeException(sprintf('The datatable shell does not contain "%s".', $expectedShellContent));
    }
}

foreach (['table-striped', 'table-hover', 'class="table-responsive"'] as $unexpectedShellContent) {
    if (str_contains($shell, $unexpectedShellContent)) {
        throw new RuntimeException(sprintf('The datatable shell unexpectedly contains "%s".', $unexpectedShellContent));
    }
}

$content = file_get_contents($argv[2]);

if (!is_string($content)) {
    throw new RuntimeException('The fragments response could not be read.');
}

$payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

if (20 !== ($payload['totalItems'] ?? null)) {
    throw new RuntimeException('The fragments response has an invalid total item count.');
}

$body = $payload['body'] ?? null;

if (
    !is_string($body)
    || !str_contains($body, 'alice@example.test')
    || !str_contains($body, 'bob@example.test')
    || !str_contains($body, 'smoke-icon-view')
    || !str_contains($body, '/smoke/users/1')
    || !str_contains($body, 'data-bs-toggle="dropdown"')
    || !str_contains($body, 'name="selected_rows[]"')
) {
    throw new RuntimeException('The fragments response does not contain the expected rows and row action.');
}

$csv = file_get_contents($argv[3]);

if (!is_string($csv)) {
    throw new RuntimeException('The CSV response could not be read.');
}

if (!str_starts_with($csv, "\xEF\xBB\xBF")) {
    throw new RuntimeException('The CSV response does not contain the configured UTF-8 BOM.');
}

if (!str_contains($csv, "Email;Enabled\n") || !str_contains($csv, "alice@example.test;1\n")) {
    throw new RuntimeException('The CSV response does not use the configured delimiter.');
}

fwrite(STDOUT, "Fresh Symfony application smoke test passed.\n");
