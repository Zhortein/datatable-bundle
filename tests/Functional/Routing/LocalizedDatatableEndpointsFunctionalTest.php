<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Routing;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\LocalizedRoutesTestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class LocalizedDatatableEndpointsFunctionalTest extends FunctionalTestCase
{
    public function test_localized_routes_drive_every_default_endpoint(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $router = $container->get(RouterInterface::class);

        self::assertInstanceOf(RouterInterface::class, $router);
        $router->getContext()->setParameter('_locale', 'fr');

        $twig = $container->get(Environment::class);
        self::assertInstanceOf(Environment::class, $twig);

        $html = $twig
            ->createTemplate(
                '{{ zhortein_datatable("array-users", {'
                .'exportFormats: ["csv", "xlsx"], savedViews: true, savedViewsLocale: "fr"'
                .'}) }}',
            )
            ->render()
        ;

        self::assertSame(
            '/fr/_zhortein/datatable/array-users/fragments',
            $this->extractAttribute(
                $html,
                'data-zhortein--datatable-bundle--datatable-fragments-url-value',
            ),
        );
        self::assertSame(
            '/fr/_zhortein/datatable/array-users/export',
            $this->extractAttribute(
                $html,
                'data-zhortein--datatable-bundle--datatable-export-url-value',
            ),
        );
        self::assertStringContainsString(
            'data-zhortein--datatable-bundle--datatable-export-url-param="/fr/_zhortein/datatable/array-users/export/xlsx"',
            $html,
        );
        self::assertStringStartsWith(
            '/fr/_zhortein/datatable/array-users/views?',
            $this->extractAttribute(
                $html,
                'data-zhortein--datatable-bundle--datatable-saved-views-url-value',
            ),
        );
    }

    public function test_parent_child_shell_and_fragments_stay_under_the_localized_prefix(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();
        $router = $container->get(RouterInterface::class);

        self::assertInstanceOf(RouterInterface::class, $router);
        $router->getContext()->setParameter('_locale', 'fr');

        $twig = $container->get(Environment::class);
        self::assertInstanceOf(Environment::class, $twig);

        $parentShell = $twig
            ->createTemplate('{{ zhortein_datatable("localized-parents") }}')
            ->render()
        ;
        $parentFragmentsUrl = $this->extractAttribute(
            $parentShell,
            'data-zhortein--datatable-bundle--datatable-fragments-url-value',
        );

        self::assertSame(
            '/fr/_zhortein/datatable/localized-parents/fragments',
            $parentFragmentsUrl,
        );

        $parentFragments = $kernel->handle(Request::create($parentFragmentsUrl));
        self::assertSame(200, $parentFragments->getStatusCode());
        $parentPayload = $this->decodeJsonResponse($parentFragments->getContent());
        $childShellUrl = $this->extractAttribute(
            $parentPayload['body'],
            'data-zhortein--datatable-bundle--datatable-child-url',
        );

        self::assertStringStartsWith(
            '/fr/_zhortein/datatable/localized-children/child?',
            $childShellUrl,
        );

        $childShellResponse = $kernel->handle(Request::create($childShellUrl));
        self::assertSame(200, $childShellResponse->getStatusCode());
        $childShell = $childShellResponse->getContent();
        self::assertIsString($childShell);
        $childFragmentsUrl = $this->extractAttribute(
            $childShell,
            'data-zhortein--datatable-bundle--datatable-fragments-url-value',
        );

        self::assertStringStartsWith(
            '/fr/_zhortein/datatable/localized-children/fragments?',
            $childFragmentsUrl,
        );

        $childFragments = $kernel->handle(Request::create($childFragmentsUrl));
        self::assertSame(200, $childFragments->getStatusCode());
        $childPayload = $this->decodeJsonResponse($childFragments->getContent());

        self::assertStringContainsString('Child row', $childPayload['body']);
    }

    protected static function getKernelClass(): string
    {
        return LocalizedRoutesTestKernel::class;
    }

    private function extractAttribute(string $html, string $attribute): string
    {
        $matched = preg_match(
            sprintf('/%s="([^"]+)"/', preg_quote($attribute, '/')),
            $html,
            $matches,
        );

        self::assertSame(1, $matched);

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
    }

    /**
     * @return array{body: string}
     */
    private function decodeJsonResponse(string|false $content): array
    {
        self::assertIsString($content);
        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($payload);
        self::assertIsString($payload['body'] ?? null);

        return ['body' => $payload['body']];
    }
}
