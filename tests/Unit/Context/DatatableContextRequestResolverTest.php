<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Context;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Context\DatatableContextRequestResolver;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

final class DatatableContextRequestResolverTest extends TestCase
{
    public function test_it_restores_a_signed_context_and_returns_the_instance(): void
    {
        $transport = new DatatableContextTransport('unit-test-secret');
        $resolver = new DatatableContextRequestResolver($transport);
        $definition = $this->createDefinition('en');
        $token = $transport->createToken(
            'articles',
            'french-table',
            new DatatableContext(['locale' => 'fr'], ['locale']),
        );

        self::assertNotNull($token);

        $instance = $resolver->resolve(new Request([
            DatatableContextTransport::INSTANCE_QUERY_PARAMETER => 'french-table',
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER => $token,
        ]), $definition);

        self::assertSame('french-table', $instance);
        self::assertSame('fr', $definition->getContext()->get('locale'));
    }

    public function test_it_keeps_server_context_when_no_token_is_present(): void
    {
        $resolver = new DatatableContextRequestResolver(new DatatableContextTransport('unit-test-secret'));
        $definition = $this->createDefinition('fr');

        self::assertSame('articles', $resolver->resolve(new Request(), $definition));
        self::assertSame('fr', $definition->getContext()->get('locale'));
    }

    public function test_it_rejects_an_array_token_parameter(): void
    {
        $resolver = new DatatableContextRequestResolver(new DatatableContextTransport('unit-test-secret'));

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The "_zd_context" query parameter must be a non-empty string.');

        $resolver->resolve(new Request([
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER => ['invalid'],
        ]), $this->createDefinition('en'));
    }

    public function test_it_rejects_a_token_from_another_instance(): void
    {
        $transport = new DatatableContextTransport('unit-test-secret');
        $resolver = new DatatableContextRequestResolver($transport);
        $token = $transport->createToken(
            'articles',
            'french-table',
            new DatatableContext(['locale' => 'fr'], ['locale']),
        );

        self::assertNotNull($token);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The datatable context is invalid.');

        $resolver->resolve(new Request([
            DatatableContextTransport::INSTANCE_QUERY_PARAMETER => 'english-table',
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER => $token,
        ]), $this->createDefinition('en'));
    }

    private function createDefinition(string $locale): DatatableDefinition
    {
        $definition = new DatatableDefinition('articles');
        $definition->setContext(new DatatableContext(['locale' => $locale], ['locale']));

        return $definition;
    }
}
