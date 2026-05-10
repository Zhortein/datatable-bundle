<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Zhortein\DatatableBundle\DateTime\DateTimeFormatterInterface;
use Zhortein\DatatableBundle\DateTime\DefaultDateTimeFormatter;

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
        $twig->addExtension($this->createDateTimeTestExtension());
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
