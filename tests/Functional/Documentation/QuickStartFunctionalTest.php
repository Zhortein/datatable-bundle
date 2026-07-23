<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Documentation;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class QuickStartFunctionalTest extends FunctionalTestCase
{
    public function test_documented_array_datatable_path_works_end_to_end(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();

        $definitionFactory = $container->get('test.'.DatatableDefinitionFactory::class);
        self::assertInstanceOf(DatatableDefinitionFactory::class, $definitionFactory);

        $definition = $definitionFactory->create('array-users');

        self::assertSame(
            ArrayDataProvider::PROVIDER_NAME,
            $definition->getOption(DataProviderRegistry::OPTION_PROVIDER),
        );

        $twig = $container->get(Environment::class);
        self::assertInstanceOf(Environment::class, $twig);

        $shell = $twig
            ->createTemplate('{{ zhortein_datatable("array-users", {search: true}) }}')
            ->render()
        ;

        self::assertStringContainsString('data-controller="zhortein--datatable-bundle--datatable"', $shell);
        self::assertStringContainsString('/_zhortein/datatable/array-users/fragments', $shell);

        $response = $kernel->handle(
            Request::create('/_zhortein/datatable/array-users/fragments'),
        );

        self::assertSame(200, $response->getStatusCode());

        $content = $response->getContent();
        self::assertIsString($content);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        self::assertIsString($payload['header']);
        self::assertIsString($payload['body']);
        self::assertStringContainsString('Email', $payload['header']);
        self::assertStringContainsString('alice@example.test', $payload['body']);
        self::assertStringContainsString('bob@example.test', $payload['body']);
        self::assertSame(2, $payload['totalItems']);
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
