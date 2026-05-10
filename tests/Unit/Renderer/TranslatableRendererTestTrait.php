<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Twig\Environment;

trait TranslatableRendererTestTrait
{
    private function addTranslationExtension(Environment $twig, string $locale = 'en'): void
    {
        $translator = new Translator($locale);
        $translator->addLoader('yaml', new YamlFileLoader());
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

        $twig->addExtension(new TranslationExtension($translator));
    }
}
