<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Translation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

final class TranslationCatalogTest extends TestCase
{
    public function test_english_catalog_contains_builtin_messages(): void
    {
        $translator = $this->createTranslator('en');

        self::assertSame('Search', $translator->trans('zhortein_datatable.search.label', [], 'zhortein_datatable'));
        self::assertSame('Rows per page', $translator->trans('zhortein_datatable.page_size.label', [], 'zhortein_datatable'));
        self::assertSame('Loading...', $translator->trans('zhortein_datatable.loading', [], 'zhortein_datatable'));
        self::assertSame('No data available.', $translator->trans('zhortein_datatable.empty', [], 'zhortein_datatable'));
        self::assertSame('Actions', $translator->trans('zhortein_datatable.actions', [], 'zhortein_datatable'));
        self::assertSame('Previous', $translator->trans('zhortein_datatable.pagination.previous', [], 'zhortein_datatable'));
        self::assertSame('Next', $translator->trans('zhortein_datatable.pagination.next', [], 'zhortein_datatable'));
        self::assertSame('Yes', $translator->trans('zhortein_datatable.boolean.yes', [], 'zhortein_datatable'));
        self::assertSame('No', $translator->trans('zhortein_datatable.boolean.no', [], 'zhortein_datatable'));
    }

    public function test_french_catalog_contains_builtin_messages(): void
    {
        $translator = $this->createTranslator('fr');

        self::assertSame('Rechercher', $translator->trans('zhortein_datatable.search.label', [], 'zhortein_datatable'));
        self::assertSame('Lignes par page', $translator->trans('zhortein_datatable.page_size.label', [], 'zhortein_datatable'));
        self::assertSame('Chargement...', $translator->trans('zhortein_datatable.loading', [], 'zhortein_datatable'));
        self::assertSame('Aucune donnée disponible.', $translator->trans('zhortein_datatable.empty', [], 'zhortein_datatable'));
        self::assertSame('Précédent', $translator->trans('zhortein_datatable.pagination.previous', [], 'zhortein_datatable'));
        self::assertSame('Suivant', $translator->trans('zhortein_datatable.pagination.next', [], 'zhortein_datatable'));
        self::assertSame('Oui', $translator->trans('zhortein_datatable.boolean.yes', [], 'zhortein_datatable'));
        self::assertSame('Non', $translator->trans('zhortein_datatable.boolean.no', [], 'zhortein_datatable'));
    }

    private function createTranslator(string $locale): Translator
    {
        $translator = new Translator($locale);
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource(
            'yaml',
            __DIR__.'/../../../translations/zhortein_datatable.'.$locale.'.yaml',
            $locale,
            'zhortein_datatable',
        );

        return $translator;
    }
}
