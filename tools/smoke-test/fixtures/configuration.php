<?php

declare(strict_types=1);

use App\Kernel;

require __DIR__.'/vendor/autoload.php';

if (4 !== $argc) {
    throw new InvalidArgumentException('Expected a configuration profile, environment and debug:config output path.');
}

$profiles = [
    'minimal' => [
        'patterns' => [
            'default_provider:\s+doctrine',
            'default_theme:\s+bootstrap',
            'default_page_size:\s+25',
            'max_page_size:\s+500',
            'search_enabled:\s+false',
            'search_builder_enabled:\s+false',
            'icon_provider:\s+css',
            'icons:\s+(?:\[\]|\{\s*\})',
            'striped:\s+true',
            'hover:\s+true',
            'bordered:\s+false',
            'borderless:\s+false',
            'small:\s+false',
            'responsive:\s+true',
            'delimiter:',
            'enclosure:',
            'escape:',
            'bom:\s+false',
        ],
        'parameters' => [
            'zhortein_datatable.default_provider' => 'doctrine',
            'zhortein_datatable.default_theme' => 'bootstrap',
            'zhortein_datatable.default_page_size' => 25,
            'zhortein_datatable.max_page_size' => 500,
            'zhortein_datatable.search_enabled' => false,
            'zhortein_datatable.search_builder_enabled' => false,
            'zhortein_datatable.icon_provider' => 'css',
            'zhortein_datatable.icons' => [],
            'zhortein_datatable.bootstrap.table_striped' => true,
            'zhortein_datatable.bootstrap.table_hover' => true,
            'zhortein_datatable.bootstrap.table_bordered' => false,
            'zhortein_datatable.bootstrap.table_borderless' => false,
            'zhortein_datatable.bootstrap.table_small' => false,
            'zhortein_datatable.bootstrap.table_responsive' => true,
            'zhortein_datatable.export.csv.delimiter' => ',',
            'zhortein_datatable.export.csv.enclosure' => '"',
            'zhortein_datatable.export.csv.escape' => '\\',
            'zhortein_datatable.export.csv.bom' => false,
        ],
    ],
    'complete' => [
        'patterns' => [
            'default_provider:\s+array',
            'default_theme:\s+bootstrap',
            'default_page_size:\s+15',
            'max_page_size:\s+120',
            'search_enabled:\s+true',
            'search_builder_enabled:\s+true',
            'icon_provider:\s+'.('1' === getenv('SMOKE_UX_ICONS') ? 'ux_icons' : 'css'),
            'action_view:\s+smoke-icon-view',
            'striped:\s+false',
            'hover:\s+false',
            'bordered:\s+true',
            'borderless:\s+false',
            'small:\s+true',
            'responsive:\s+false',
            'delimiter:\s+[\'"]?;[\'"]?',
            'enclosure:',
            'escape:',
            'bom:\s+true',
        ],
        'parameters' => [
            'zhortein_datatable.default_provider' => 'array',
            'zhortein_datatable.default_theme' => 'bootstrap',
            'zhortein_datatable.default_page_size' => 15,
            'zhortein_datatable.max_page_size' => 120,
            'zhortein_datatable.search_enabled' => true,
            'zhortein_datatable.search_builder_enabled' => true,
            'zhortein_datatable.icon_provider' => '1' === getenv('SMOKE_UX_ICONS') ? 'ux_icons' : 'css',
            'zhortein_datatable.icons' => ['action_view' => 'smoke-icon-view'],
            'zhortein_datatable.bootstrap.table_striped' => false,
            'zhortein_datatable.bootstrap.table_hover' => false,
            'zhortein_datatable.bootstrap.table_bordered' => true,
            'zhortein_datatable.bootstrap.table_borderless' => false,
            'zhortein_datatable.bootstrap.table_small' => true,
            'zhortein_datatable.bootstrap.table_responsive' => false,
            'zhortein_datatable.export.csv.delimiter' => ';',
            'zhortein_datatable.export.csv.enclosure' => '|',
            'zhortein_datatable.export.csv.escape' => '!',
            'zhortein_datatable.export.csv.bom' => true,
        ],
    ],
];

$profile = $profiles[$argv[1]] ?? null;

if (!is_array($profile)) {
    throw new InvalidArgumentException(sprintf('Unknown configuration profile "%s".', $argv[1]));
}

$config = file_get_contents($argv[3]);

if (!is_string($config)) {
    throw new RuntimeException('The debug:config output could not be read.');
}

assertConfigValues($config, $profile['patterns']);

$kernel = new Kernel($argv[2], true);
$kernel->boot();
$container = $kernel->getContainer();

foreach ($profile['parameters'] as $name => $expectedValue) {
    $actualValue = $container->getParameter($name);

    if ($expectedValue !== $actualValue) {
        throw new RuntimeException(sprintf(
            'Container parameter "%s" has an unexpected value: %s.',
            $name,
            var_export($actualValue, true),
        ));
    }
}

$kernel->shutdown();

fwrite(STDOUT, "Fresh Symfony application configuration assertions passed.\n");

/**
 * @param list<string> $patterns
 */
function assertConfigValues(string $config, array $patterns): void
{
    foreach ($patterns as $pattern) {
        if (1 !== preg_match('/'.$pattern.'/', $config)) {
            throw new RuntimeException(sprintf(
                'debug:config output does not match pattern "%s".',
                $pattern,
            ));
        }
    }
}
