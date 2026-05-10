<?php

declare(strict_types=1);

$projectDir = dirname(__DIR__, 2);
$changelogPath = $projectDir.'/CHANGELOG.md';
$fragmentsDir = $projectDir.'/changelog/unreleased';

$sectionTitles = [
    'added' => 'Added',
    'changed' => 'Changed',
    'deprecated' => 'Deprecated',
    'removed' => 'Removed',
    'fixed' => 'Fixed',
    'security' => 'Security',
];

if (!is_file($changelogPath)) {
    fwrite(STDERR, sprintf("CHANGELOG.md not found at %s\n", $changelogPath));
    exit(1);
}

$fragments = [];

foreach ($sectionTitles as $type => $title) {
    $fragments[$type] = [];
}

if (is_dir($fragmentsDir)) {
    $files = glob($fragmentsDir.'/*.md') ?: [];

    sort($files);

    foreach ($files as $file) {
        $filename = basename($file);
        $type = strtok($filename, '-');

        if (!is_string($type) || !array_key_exists($type, $sectionTitles)) {
            fwrite(STDERR, sprintf(
                "Skipping changelog fragment with unknown type: %s\n",
                $filename,
            ));
            continue;
        }

        $content = trim((string) file_get_contents($file));

        if ('' === $content) {
            continue;
        }

        $fragments[$type][] = $content;
    }
}

$unreleasedLines = [
    '## [Unreleased]',
    '',
];

$hasFragments = false;

foreach ($sectionTitles as $type => $title) {
    if ([] === $fragments[$type]) {
        continue;
    }

    $hasFragments = true;
    $unreleasedLines[] = sprintf('### %s', $title);
    $unreleasedLines[] = '';

    foreach ($fragments[$type] as $fragment) {
        foreach (preg_split('/\R/', $fragment) ?: [] as $line) {
            $line = rtrim($line);

            if ('' === $line) {
                continue;
            }

            $unreleasedLines[] = str_starts_with($line, '- ') ? $line : '- '.$line;
        }
    }

    $unreleasedLines[] = '';
}

if (!$hasFragments) {
    $unreleasedLines[] = '_No unreleased changes have been collected yet._';
    $unreleasedLines[] = '';
}

$changelog = (string) file_get_contents($changelogPath);

$pattern = '/## \[Unreleased\]\R(?:.*?)(?=\R## \[|\z)/s';

if (1 !== preg_match($pattern, $changelog)) {
    fwrite(STDERR, "Unable to locate the [Unreleased] section in CHANGELOG.md\n");
    exit(1);
}

$newChangelog = preg_replace(
    $pattern,
    rtrim(implode(PHP_EOL, $unreleasedLines)),
    $changelog,
    count: $count,
);

if (1 !== $count || !is_string($newChangelog)) {
    fwrite(STDERR, "Unable to update the [Unreleased] section in CHANGELOG.md\n");
    exit(1);
}

file_put_contents($changelogPath, rtrim($newChangelog).PHP_EOL);

echo "CHANGELOG.md updated.\n";
