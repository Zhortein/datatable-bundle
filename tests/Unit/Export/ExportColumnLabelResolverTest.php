<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Export\ExportColumnLabelResolver;

final class ExportColumnLabelResolverTest extends TestCase
{
    public function test_it_translates_explicit_labels_in_the_definition_domain(): void
    {
        $translator = new Translator('fr');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource(
            'array',
            ['users.columns.email' => 'Adresse e-mail'],
            'fr',
            'users',
        );
        $definition = new DatatableDefinition('users');
        $definition->setTranslationDomain('users');

        $label = (new ExportColumnLabelResolver($translator))->resolve(
            $definition,
            new ColumnDefinition('e.email', 'users.columns.email'),
        );

        self::assertSame('Adresse e-mail', $label);
    }

    public function test_it_keeps_literal_and_fallback_labels_out_of_translation_catalogs(): void
    {
        $translator = new Translator('fr');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource(
            'array',
            [
                'Email' => 'Adresse e-mail',
                'e.reference' => 'Référence',
            ],
            'fr',
            'users',
        );
        $resolver = new ExportColumnLabelResolver($translator);
        $literalDefinition = new DatatableDefinition('users');
        $translatedDefinition = new DatatableDefinition('users');
        $translatedDefinition->setTranslationDomain('users');

        self::assertSame(
            'Email',
            $resolver->resolve($literalDefinition, new ColumnDefinition('e.email', 'Email')),
        );
        self::assertSame(
            'e.reference',
            $resolver->resolve($translatedDefinition, new ColumnDefinition('e.reference')),
        );
    }
}
