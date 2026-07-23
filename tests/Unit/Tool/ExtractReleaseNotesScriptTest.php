<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Tool;

use PHPUnit\Framework\TestCase;

final class ExtractReleaseNotesScriptTest extends TestCase
{
    public function test_it_extracts_notes_from_a_matching_version_section(): void
    {
        [$exitCode, $output] = $this->runScript('v0.3.0-beta.1');

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Advanced filter expressions', $output);
        self::assertStringNotContainsString('0.2.0-alpha.1', $output);
    }

    public function test_it_rejects_an_invalid_tag(): void
    {
        [$exitCode, $output] = $this->runScript('0.3.0-beta.1');

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Invalid tag format', $output);
    }

    public function test_it_rejects_a_tag_without_a_matching_changelog_section(): void
    {
        [$exitCode, $output] = $this->runScript('v9.9.9');

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('No changelog section found for tag v9.9.9', $output);
        self::assertStringNotContainsString('Unreleased', $output);
    }

    /**
     * @return array{int, string}
     */
    private function runScript(string $tag): array
    {
        $script = dirname(__DIR__, 3).'/tools/changelog/extract-release-notes.php';
        $output = [];
        $exitCode = 0;

        exec(
            sprintf(
                '%s %s %s 2>&1',
                escapeshellarg(PHP_BINARY),
                escapeshellarg($script),
                escapeshellarg($tag),
            ),
            $output,
            $exitCode,
        );

        return [$exitCode, implode(PHP_EOL, $output)];
    }
}
