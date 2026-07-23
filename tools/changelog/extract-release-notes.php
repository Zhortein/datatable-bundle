<?php

declare(strict_types=1);

if (2 !== $argc) {
    fwrite(STDERR, "Usage: php tools/changelog/extract-release-notes.php <tag>\n");
    exit(1);
}

$tag = $argv[1];

if (!preg_match('/^v(?<version>\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?)$/', $tag, $matches)) {
    fwrite(STDERR, sprintf("Invalid tag format: %s\n", $tag));
    exit(1);
}

$version = $matches['version'];
$projectDir = dirname(__DIR__, 2);
$changelogPath = $projectDir.'/CHANGELOG.md';

if (!is_file($changelogPath)) {
    fwrite(STDERR, sprintf("CHANGELOG.md not found at %s\n", $changelogPath));
    exit(1);
}

$changelog = (string) file_get_contents($changelogPath);

$patterns = [
    sprintf('/## \[%s\](?: - \d{4}-\d{2}-\d{2})?\R(?<notes>.*?)(?=\R## \[|\z)/s', preg_quote($version, '/')),
    sprintf('/## \[%s\](?: - \d{4}-\d{2}-\d{2})?\R(?<notes>.*?)(?=\R## \[|\z)/s', preg_quote($tag, '/')),
];

foreach ($patterns as $pattern) {
    if (1 === preg_match($pattern, $changelog, $match)) {
        $notes = trim($match['notes']);

        if ('' === $notes) {
            fwrite(STDERR, sprintf("Changelog section for tag %s is empty.\n", $tag));
            exit(1);
        }

        echo $notes.PHP_EOL;
        exit(0);
    }
}

fwrite(STDERR, sprintf("No changelog section found for tag %s.\n", $tag));
exit(1);
