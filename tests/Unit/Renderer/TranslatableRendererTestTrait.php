<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Zhortein\DatatableBundle\DateTime\DateTimeFormatterInterface;
use Zhortein\DatatableBundle\DateTime\DefaultDateTimeFormatter;
use Zhortein\DatatableBundle\Twig\DeclarativeTranslationExtension;

trait TranslatableRendererTestTrait
{
    private function addTranslationExtension(Environment $twig, string $locale = 'en'): Translator
    {
        $translator = new Translator($locale);
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource(
            'yaml',
            __DIR__.'/../../../translations/zhortein_datatable.en.yaml',
            'en',
            'zhortein_datatable',
        );
        $translator->addResource(
            'yaml',
            __DIR__.'/../../../translations/zhortein_datatable.fr.yaml',
            'fr',
            'zhortein_datatable',
        );
        $translator->addResource('array', [
            'datatable.column.email' => 'Email address',
            'datatable.filter.email' => 'Email filter',
            'datatable.filter.email_placeholder' => 'Search an email address',
            'datatable.choice.enabled' => 'Enabled',
            'datatable.choice.disabled' => 'Disabled',
            'datatable.advanced.status' => 'Status rule',
            'datatable.action.view' => 'View user',
            'datatable.action.create' => 'Create user',
            'datatable.action.delete_selected' => 'Delete selected',
            'datatable.confirmation.view' => 'Open this user?',
            'datatable.confirmation.create' => 'Create a new user?',
            'datatable.confirmation.delete_selected' => 'Delete the selected users?',
            'Already translated' => 'This must not replace a literal',
        ], 'en', 'datatable_test');
        $translator->addResource('array', [
            'datatable.column.email' => 'Adresse e-mail',
            'datatable.filter.email' => 'Filtre par e-mail',
            'datatable.filter.email_placeholder' => 'Rechercher une adresse e-mail',
            'datatable.choice.enabled' => 'Activé',
            'datatable.choice.disabled' => 'Désactivé',
            'datatable.advanced.status' => 'Règle de statut',
            'datatable.action.view' => 'Voir l’utilisateur',
            'datatable.action.create' => 'Créer un utilisateur',
            'datatable.action.delete_selected' => 'Supprimer la sélection',
            'datatable.confirmation.view' => 'Ouvrir cet utilisateur ?',
            'datatable.confirmation.create' => 'Créer un nouvel utilisateur ?',
            'datatable.confirmation.delete_selected' => 'Supprimer les utilisateurs sélectionnés ?',
            'Already translated' => 'Ceci ne doit pas remplacer un texte littéral',
        ], 'fr', 'datatable_test');

        $twig->addExtension(new TranslationExtension($translator));
        $twig->addExtension(new DeclarativeTranslationExtension($translator));
        $twig->addExtension($this->createDateTimeTestExtension());

        return $translator;
    }

    private function createDateTimeTestExtension(): AbstractExtension
    {
        return new class(new DefaultDateTimeFormatter()) extends AbstractExtension {
            public function __construct(
                private readonly DateTimeFormatterInterface $dateTimeFormatter,
            ) {
            }

            /**
             * @return list<TwigFunction>
             */
            public function getFunctions(): array
            {
                return [
                    new TwigFunction('zhortein_datatable_datetime', $this->formatDateTime(...)),
                ];
            }

            public function formatDateTime(mixed $value): string
            {
                return $this->dateTimeFormatter->format($value);
            }
        };
    }
}
