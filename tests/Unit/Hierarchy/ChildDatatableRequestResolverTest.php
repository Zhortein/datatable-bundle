<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Hierarchy;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Hierarchy\AllowAllChildDatatableAuthorizationChecker;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableInstanceFactory;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableRequestResolver;
use Zhortein\DatatableBundle\Hierarchy\DenyAllChildDatatableAuthorizationChecker;

final class ChildDatatableRequestResolverTest extends TestCase
{
    public function test_it_restores_and_authorizes_a_signed_child_request(): void
    {
        $transport = new DatatableContextTransport('unit-test-secret');
        $instanceFactory = new ChildDatatableInstanceFactory();
        $instance = $instanceFactory->create('orders', 'orders-table', 'order-lines', 42, 2);
        $token = $transport->createRequiredToken(
            'order-lines',
            $instance,
            new DatatableContext(['orderId' => 42], ['orderId']),
        );
        $definition = $this->createDefinition();
        $resolver = new ChildDatatableRequestResolver(
            $transport,
            $instanceFactory,
            new AllowAllChildDatatableAuthorizationChecker(),
        );

        $resolved = $resolver->resolve(new Request([
            DatatableContextTransport::INSTANCE_QUERY_PARAMETER => $instance,
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER => $token,
        ]), $definition);

        self::assertTrue($resolver->supports($instance));
        self::assertFalse($resolver->supports('order-lines'));
        self::assertSame($instance, $resolved->getInstance());
        self::assertSame(2, $resolved->getDepth());
        self::assertSame(42, $definition->getContext()->get('orderId'));
    }

    /**
     * @param array<string, mixed> $query
     */
    #[DataProvider('missingCoordinateProvider')]
    public function test_it_requires_both_signed_request_coordinates(array $query, string $parameter): void
    {
        $resolver = new ChildDatatableRequestResolver(
            new DatatableContextTransport('unit-test-secret'),
            new ChildDatatableInstanceFactory(),
            new AllowAllChildDatatableAuthorizationChecker(),
        );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(sprintf('The "%s" query parameter must be a non-empty string.', $parameter));

        $resolver->resolve(new Request($query), $this->createDefinition());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function missingCoordinateProvider(): iterable
    {
        yield 'missing instance' => [[
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER => 'token',
        ], DatatableContextTransport::INSTANCE_QUERY_PARAMETER];
        yield 'array instance' => [[
            DatatableContextTransport::INSTANCE_QUERY_PARAMETER => ['invalid'],
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER => 'token',
        ], DatatableContextTransport::INSTANCE_QUERY_PARAMETER];
        yield 'missing token' => [[
            DatatableContextTransport::INSTANCE_QUERY_PARAMETER => 'instance',
        ], DatatableContextTransport::CONTEXT_QUERY_PARAMETER];
        yield 'array token' => [[
            DatatableContextTransport::INSTANCE_QUERY_PARAMETER => 'instance',
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER => ['invalid'],
        ], DatatableContextTransport::CONTEXT_QUERY_PARAMETER];
    }

    public function test_it_rejects_a_non_child_instance_even_with_a_valid_token(): void
    {
        $transport = new DatatableContextTransport('unit-test-secret');
        $token = $transport->createRequiredToken(
            'order-lines',
            'order-lines',
            new DatatableContext(['orderId' => 42], ['orderId']),
        );
        $resolver = new ChildDatatableRequestResolver(
            $transport,
            new ChildDatatableInstanceFactory(),
            new AllowAllChildDatatableAuthorizationChecker(),
        );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The child datatable request is invalid.');

        $resolver->resolve(new Request([
            DatatableContextTransport::INSTANCE_QUERY_PARAMETER => 'order-lines',
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER => $token,
        ]), $this->createDefinition());
    }

    public function test_it_rejects_a_token_bound_to_another_child_name(): void
    {
        $transport = new DatatableContextTransport('unit-test-secret');
        $instance = new ChildDatatableInstanceFactory()->create(
            'orders',
            'orders-table',
            'order-lines',
            42,
            1,
        );
        $token = $transport->createRequiredToken(
            'shipments',
            $instance,
            new DatatableContext(['orderId' => 42], ['orderId']),
        );
        $resolver = new ChildDatatableRequestResolver(
            $transport,
            new ChildDatatableInstanceFactory(),
            new AllowAllChildDatatableAuthorizationChecker(),
        );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The child datatable request is invalid.');

        $resolver->resolve(new Request([
            DatatableContextTransport::INSTANCE_QUERY_PARAMETER => $instance,
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER => $token,
        ]), $this->createDefinition());
    }

    public function test_it_returns_forbidden_when_authorization_is_denied(): void
    {
        $transport = new DatatableContextTransport('unit-test-secret');
        $instance = new ChildDatatableInstanceFactory()->create(
            'orders',
            'orders-table',
            'order-lines',
            42,
            1,
        );
        $token = $transport->createRequiredToken(
            'order-lines',
            $instance,
            new DatatableContext(['orderId' => 42], ['orderId']),
        );
        $resolver = new ChildDatatableRequestResolver(
            $transport,
            new ChildDatatableInstanceFactory(),
            new DenyAllChildDatatableAuthorizationChecker(),
        );

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access to the child datatable was denied.');

        $resolver->resolve(new Request([
            DatatableContextTransport::INSTANCE_QUERY_PARAMETER => $instance,
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER => $token,
        ]), $this->createDefinition());
    }

    private function createDefinition(): DatatableDefinition
    {
        return new DatatableDefinition('order-lines')
            ->setContext(new DatatableContext(['orderId' => null], ['orderId']))
        ;
    }
}
