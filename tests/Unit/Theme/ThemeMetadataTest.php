<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Theme;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\ThemeAssetOwner;
use Zhortein\DatatableBundle\Enum\ThemeAssetType;
use Zhortein\DatatableBundle\Enum\ThemeCapability;
use Zhortein\DatatableBundle\Theme\ThemeAssetRequirement;
use Zhortein\DatatableBundle\Theme\ThemeMetadata;

final class ThemeMetadataTest extends TestCase
{
    public function test_it_exposes_immutable_theme_metadata(): void
    {
        $asset = new ThemeAssetRequirement(
            'example/theme',
            ThemeAssetType::Stylesheet,
            ThemeAssetOwner::ThemePackage,
        );
        $metadata = new ThemeMetadata(
            name: 'example',
            templatePrefix: '@ExampleTheme/datatable/',
            capabilities: [ThemeCapability::Actions, ThemeCapability::Pagination],
            assetRequirements: [$asset],
        );

        self::assertSame('example', $metadata->getName());
        self::assertSame('@ExampleTheme/datatable', $metadata->getTemplatePrefix());
        self::assertSame('@ExampleTheme/datatable/_body.html.twig', $metadata->template('_body.html.twig'));
        self::assertTrue($metadata->supports(ThemeCapability::Actions));
        self::assertFalse($metadata->supports(ThemeCapability::Hierarchy));
        self::assertSame([ThemeCapability::Actions, ThemeCapability::Pagination], $metadata->getCapabilities());
        self::assertSame([$asset], $metadata->getAssetRequirements());
    }

    /**
     * @param iterable<ThemeCapability> $capabilities
     */
    #[DataProvider('invalidMetadataProvider')]
    public function test_it_rejects_invalid_metadata(
        string $name,
        string $templatePrefix,
        iterable $capabilities,
    ): void {
        $this->expectException(\InvalidArgumentException::class);

        new ThemeMetadata($name, $templatePrefix, $capabilities);
    }

    /**
     * @return iterable<string, array{name: string, templatePrefix: string, capabilities: iterable<ThemeCapability>}>
     */
    public static function invalidMetadataProvider(): iterable
    {
        yield 'invalid name' => [
            'name' => 'Invalid Theme',
            'templatePrefix' => '@Theme',
            'capabilities' => [],
        ];

        yield 'empty template prefix' => [
            'name' => 'valid',
            'templatePrefix' => ' ',
            'capabilities' => [],
        ];
    }

    public function test_it_rejects_template_traversal(): void
    {
        $metadata = new ThemeMetadata('example', '@ExampleTheme', []);

        $this->expectException(\InvalidArgumentException::class);

        $metadata->template('../bootstrap/datatable.html.twig');
    }

    public function test_it_rejects_an_empty_asset_package(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ThemeAssetRequirement(
            ' ',
            ThemeAssetType::Stylesheet,
            ThemeAssetOwner::ThemePackage,
        );
    }
}
