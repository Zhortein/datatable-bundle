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

$externalThemeEnabled = '1' === getenv('SMOKE_EXTERNAL_THEME');
$expectedShellContents = [
    'data-zhortein--datatable-bundle--datatable-page-size-value="15"',
    'data-zhortein--datatable-bundle--datatable-search-builder-value="true"',
    'data-zhortein--datatable-bundle--datatable-target="searchInput"',
    'data-zhortein--datatable-bundle--datatable-target="searchBuilder"',
];

if (!$externalThemeEnabled) {
    $expectedShellContents[] = 'table-bordered';
    $expectedShellContents[] = 'table-sm';
}

foreach ($expectedShellContents as $expectedShellContent) {
    if (!str_contains($shell, $expectedShellContent)) {
        throw new RuntimeException(sprintf('The datatable shell does not contain "%s".', $expectedShellContent));
    }
}

if ($externalThemeEnabled) {
    foreach ([
        'data-zhortein-datatable-theme="acme"',
        'data-zhortein-datatable-template-owner="external-package"',
    ] as $expectedExternalThemeContent) {
        if (!str_contains($shell, $expectedExternalThemeContent)) {
            throw new RuntimeException(sprintf('The external theme shell does not contain "%s".', $expectedExternalThemeContent));
        }
    }
}

foreach (['table-striped', 'table-hover', 'class="table-responsive"'] as $unexpectedShellContent) {
    if (str_contains($shell, $unexpectedShellContent)) {
        throw new RuntimeException(sprintf('The datatable shell unexpectedly contains "%s".', $unexpectedShellContent));
    }
}

if ('1' === getenv('SMOKE_UX_ICONS')) {
    if (!str_contains($shell, '<svg') || !str_contains($shell, 'aria-hidden="true"')) {
        throw new RuntimeException('The Symfony UX Icons adapter did not render accessible SVG markup.');
    }

    if (str_contains($shell, 'bi bi-sliders')) {
        throw new RuntimeException('The Symfony UX Icons adapter fell back for a built-in Bootstrap icon.');
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

if (!is_string($body)) {
    throw new RuntimeException('The fragments response body is invalid.');
}

$expectedBodyContents = [
    'alice@example.test',
    'bob@example.test',
    'smoke-icon-view',
    '/smoke/users/1',
    'name="selected_rows[]"',
];

if (!$externalThemeEnabled) {
    $expectedBodyContents[] = 'data-bs-toggle="dropdown"';
}

foreach ($expectedBodyContents as $expectedBodyContent) {
    if (str_contains($body, $expectedBodyContent)) {
        continue;
    }

    throw new RuntimeException('The fragments response does not contain the expected rows and row action.');
}

if ($externalThemeEnabled) {
    if (
        !str_contains($body, 'data-zhortein-datatable-template-owner="external-package"')
        || !str_contains($body, 'acme-cell--centered')
    ) {
        throw new RuntimeException('The fragments response was not rendered by the external theme package.');
    }
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
