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
        self::assertSame('Filters are active.', $translator->trans('zhortein_datatable.filters.active', [], 'zhortein_datatable'));
        self::assertSame('Clear filters', $translator->trans('zhortein_datatable.filters.clear', [], 'zhortein_datatable'));
        self::assertSame('Columns', $translator->trans('zhortein_datatable.columns.visibility', [], 'zhortein_datatable'));
        self::assertSame('Export', $translator->trans('zhortein_datatable.export.label', [], 'zhortein_datatable'));
        self::assertSame('CSV current view', $translator->trans('zhortein_datatable.export.csv_current', [], 'zhortein_datatable'));
        self::assertSame('CSV full dataset', $translator->trans('zhortein_datatable.export.csv_full', [], 'zhortein_datatable'));
        self::assertSame('Loading...', $translator->trans('zhortein_datatable.loading', [], 'zhortein_datatable'));
        self::assertSame('Unable to load datatable data.', $translator->trans('zhortein_datatable.error.generic', [], 'zhortein_datatable'));
        self::assertSame('No data available.', $translator->trans('zhortein_datatable.empty', [], 'zhortein_datatable'));
        self::assertSame('Actions', $translator->trans('zhortein_datatable.actions.more', [], 'zhortein_datatable'));
        self::assertSame('The action completed successfully.', $translator->trans('zhortein_datatable.ajax_action.success', [], 'zhortein_datatable'));
        self::assertSame('The action could not be completed.', $translator->trans('zhortein_datatable.ajax_action.error', [], 'zhortein_datatable'));
        self::assertSame('The action returned an invalid response.', $translator->trans('zhortein_datatable.ajax_action.invalid_response', [], 'zhortein_datatable'));
        self::assertSame('Saved views', $translator->trans('zhortein_datatable.saved_views.label', [], 'zhortein_datatable'));
        self::assertSame('Set as default', $translator->trans('zhortein_datatable.saved_views.make_default', [], 'zhortein_datatable'));
        self::assertSame('The saved view changed in another request. Reload it and try again.', $translator->trans('zhortein_datatable.saved_views.conflict', [], 'zhortein_datatable'));
        self::assertSame('Sort by Email', $translator->trans('zhortein_datatable.sort.label', ['%column%' => 'Email'], 'zhortein_datatable'));
        self::assertSame('sorted ascending', $translator->trans('zhortein_datatable.sort.sorted_ascending', [], 'zhortein_datatable'));
        self::assertSame('Previous', $translator->trans('zhortein_datatable.pagination.previous', [], 'zhortein_datatable'));
        self::assertSame('Next', $translator->trans('zhortein_datatable.pagination.next', [], 'zhortein_datatable'));
        self::assertSame('Go to page 2', $translator->trans('zhortein_datatable.pagination.page', ['%page%' => 2], 'zhortein_datatable'));
        self::assertSame('Yes', $translator->trans('zhortein_datatable.boolean.yes', [], 'zhortein_datatable'));
        self::assertSame('No', $translator->trans('zhortein_datatable.boolean.no', [], 'zhortein_datatable'));
        self::assertSame('No result.', $translator->trans('zhortein_datatable.summary.empty', [], 'zhortein_datatable'));
        self::assertSame('1 result.', $translator->trans('zhortein_datatable.summary.single', [], 'zhortein_datatable'));
        self::assertSame('Filter Email', $translator->trans('zhortein_datatable.filters.column_filter', ['%column%' => 'Email'], 'zhortein_datatable'));
        self::assertSame('Filter active', $translator->trans('zhortein_datatable.filters.column_filter_active', [], 'zhortein_datatable'));
        self::assertSame('Clear filter', $translator->trans('zhortein_datatable.filters.clear_column', [], 'zhortein_datatable'));
        self::assertSame('Email from', $translator->trans('zhortein_datatable.filters.range_from', ['%filter%' => 'Email'], 'zhortein_datatable'));
        self::assertSame('Email to', $translator->trans('zhortein_datatable.filters.range_to', ['%filter%' => 'Email'], 'zhortein_datatable'));
    }

    public function test_french_catalog_contains_builtin_messages(): void
    {
        $translator = $this->createTranslator('fr');

        self::assertSame('Rechercher', $translator->trans('zhortein_datatable.search.label', [], 'zhortein_datatable'));
        self::assertSame('Lignes par page', $translator->trans('zhortein_datatable.page_size.label', [], 'zhortein_datatable'));
        self::assertSame('Des filtres sont actifs.', $translator->trans('zhortein_datatable.filters.active', [], 'zhortein_datatable'));
        self::assertSame('Effacer les filtres', $translator->trans('zhortein_datatable.filters.clear', [], 'zhortein_datatable'));
        self::assertSame('Colonnes', $translator->trans('zhortein_datatable.columns.visibility', [], 'zhortein_datatable'));
        self::assertSame('Exporter', $translator->trans('zhortein_datatable.export.label', [], 'zhortein_datatable'));
        self::assertSame('CSV vue courante', $translator->trans('zhortein_datatable.export.csv_current', [], 'zhortein_datatable'));
        self::assertSame('CSV complet', $translator->trans('zhortein_datatable.export.csv_full', [], 'zhortein_datatable'));
        self::assertSame('Chargement...', $translator->trans('zhortein_datatable.loading', [], 'zhortein_datatable'));
        self::assertSame('Impossible de charger les données du tableau.', $translator->trans('zhortein_datatable.error.generic', [], 'zhortein_datatable'));
        self::assertSame('Trier par Email', $translator->trans('zhortein_datatable.sort.label', ['%column%' => 'Email'], 'zhortein_datatable'));
        self::assertSame('tri croissant', $translator->trans('zhortein_datatable.sort.sorted_ascending', [], 'zhortein_datatable'));
        self::assertSame('Aller à la page 2', $translator->trans('zhortein_datatable.pagination.page', ['%page%' => 2], 'zhortein_datatable'));
        self::assertSame('Aucune donnée disponible.', $translator->trans('zhortein_datatable.empty', [], 'zhortein_datatable'));
        self::assertSame('Précédent', $translator->trans('zhortein_datatable.pagination.previous', [], 'zhortein_datatable'));
        self::assertSame('Suivant', $translator->trans('zhortein_datatable.pagination.next', [], 'zhortein_datatable'));
        self::assertSame('L’action a été réalisée.', $translator->trans('zhortein_datatable.ajax_action.success', [], 'zhortein_datatable'));
        self::assertSame('L’action n’a pas pu être réalisée.', $translator->trans('zhortein_datatable.ajax_action.error', [], 'zhortein_datatable'));
        self::assertSame('L’action a renvoyé une réponse invalide.', $translator->trans('zhortein_datatable.ajax_action.invalid_response', [], 'zhortein_datatable'));
        self::assertSame('Vues enregistrées', $translator->trans('zhortein_datatable.saved_views.label', [], 'zhortein_datatable'));
        self::assertSame('Définir par défaut', $translator->trans('zhortein_datatable.saved_views.make_default', [], 'zhortein_datatable'));
        self::assertSame('La vue enregistrée a été modifiée par une autre requête. Rechargez-la puis réessayez.', $translator->trans('zhortein_datatable.saved_views.conflict', [], 'zhortein_datatable'));
        self::assertSame('Oui', $translator->trans('zhortein_datatable.boolean.yes', [], 'zhortein_datatable'));
        self::assertSame('Non', $translator->trans('zhortein_datatable.boolean.no', [], 'zhortein_datatable'));
        self::assertSame('Aucun résultat.', $translator->trans('zhortein_datatable.summary.empty', [], 'zhortein_datatable'));
        self::assertSame('1 résultat.', $translator->trans('zhortein_datatable.summary.single', [], 'zhortein_datatable'));
        self::assertSame('Rechercher', $translator->trans('zhortein_datatable.search.label', [], 'zhortein_datatable'));
        self::assertSame('Filtrer Email', $translator->trans('zhortein_datatable.filters.column_filter', ['%column%' => 'Email'], 'zhortein_datatable'));
        self::assertSame('Filtre actif', $translator->trans('zhortein_datatable.filters.column_filter_active', [], 'zhortein_datatable'));
        self::assertSame('Effacer le filtre', $translator->trans('zhortein_datatable.filters.clear_column', [], 'zhortein_datatable'));
        self::assertSame('Email à partir de', $translator->trans('zhortein_datatable.filters.range_from', ['%filter%' => 'Email'], 'zhortein_datatable'));
        self::assertSame('Email jusqu’à', $translator->trans('zhortein_datatable.filters.range_to', ['%filter%' => 'Email'], 'zhortein_datatable'));
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
