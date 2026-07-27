<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Hierarchy;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Contract\ChildDatatableAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Definition\ChildContextValue;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Exception\ChildDatatableAccessDeniedException;
use Zhortein\DatatableBundle\Hierarchy\AllowAllChildDatatableAuthorizationChecker;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableAuthorizationContext;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableContextResolver;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableInstanceFactory;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableResolver;
use Zhortein\DatatableBundle\Hierarchy\DenyAllChildDatatableAuthorizationChecker;
use Zhortein\DatatableBundle\Hierarchy\RowValueAccessor;

final class ChildDatatableResolverTest extends TestCase
{
    public function test_it_builds_an_authorized_signed_child_descriptor(): void
    {
        $transport = new DatatableContextTransport('unit-test-secret');
        $resolver = $this->createResolver(
            $transport,
            new AllowAllChildDatatableAuthorizationChecker(),
        );
        $parent = new DatatableDefinition('orders')
            ->setContext(new DatatableContext(['locale' => 'fr']))
            ->setChildDatatable('order-lines', [
                'orderId' => ChildContextValue::row('o.id'),
                'locale' => ChildContextValue::context('locale'),
            ])
        ;

        $child = $resolver->resolve(
            parentDefinition: $parent,
            row: ['o_id' => 42],
            rowIdentifier: 42,
            parentInstance: 'active-orders',
        );

        self::assertSame('order-lines', $child->getName());
        self::assertSame(1, $child->getDepth());
        self::assertSame(['orderId' => 42, 'locale' => 'fr'], $child->getContext()->all());

        $restored = $transport->restore(
            $child->getContextToken(),
            $child->getName(),
            $child->getInstance(),
            new DatatableContext(
                ['orderId' => null, 'locale' => null],
                ['orderId', 'locale'],
            ),
        );

        self::assertSame(['orderId' => 42, 'locale' => 'fr'], $restored->all());
    }

    public function test_it_always_signs_an_empty_child_context(): void
    {
        $transport = new DatatableContextTransport('unit-test-secret');
        $parent = new DatatableDefinition('orders')
            ->setChildDatatable('order-lines')
        ;
        $child = $this->createResolver(
            $transport,
            new AllowAllChildDatatableAuthorizationChecker(),
        )->resolve($parent, [], 42, 'orders');

        self::assertNotSame('', $child->getContextToken());
        self::assertSame(
            [],
            $transport->restore(
                $child->getContextToken(),
                'order-lines',
                $child->getInstance(),
                new DatatableContext(),
            )->all(),
        );
    }

    public function test_it_denies_a_child_rejected_by_the_authorization_checker(): void
    {
        $parent = new DatatableDefinition('orders')
            ->setChildDatatable('order-lines')
        ;

        $this->expectException(ChildDatatableAccessDeniedException::class);
        $this->expectExceptionMessage('Access to child datatable "order-lines" at depth 1 was denied.');

        $this->createResolver(
            new DatatableContextTransport('unit-test-secret'),
            new DenyAllChildDatatableAuthorizationChecker(),
        )->resolve($parent, [], 42, 'orders');
    }

    public function test_it_passes_the_resolved_child_coordinates_to_the_authorization_checker(): void
    {
        $checker = new CapturingChildDatatableAuthorizationChecker();
        $parent = new DatatableDefinition('orders')
            ->setContext(new DatatableContext(['tenant' => 'acme']))
            ->setChildDatatable('order-lines', [
                'tenant' => ChildContextValue::context('tenant'),
            ])
        ;

        $child = $this->createResolver(
            new DatatableContextTransport('unit-test-secret'),
            $checker,
        )->resolve($parent, [], 42, 'active-orders', parentDepth: 1);

        $authorizationContext = $checker->context;
        self::assertInstanceOf(ChildDatatableAuthorizationContext::class, $authorizationContext);
        self::assertSame('order-lines', $authorizationContext->getChildDatatableName());
        self::assertSame($child->getInstance(), $authorizationContext->getChildInstance());
        self::assertSame(2, $authorizationContext->getDepth());
        self::assertSame(['tenant' => 'acme'], $authorizationContext->getContext()->all());
    }

    public function test_it_enforces_the_child_maximum_depth(): void
    {
        $parent = new DatatableDefinition('orders')
            ->setChildDatatable('order-lines', maxDepth: 2)
        ;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Child datatable "order-lines" cannot be resolved at depth 3; its maximum depth is 2.');

        $this->createResolver(
            new DatatableContextTransport('unit-test-secret'),
            new AllowAllChildDatatableAuthorizationChecker(),
        )->resolve($parent, [], 42, 'orders', parentDepth: 2);
    }

    public function test_it_rejects_a_parent_without_a_child_definition(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Datatable "orders" does not define a child datatable.');

        $this->createResolver(
            new DatatableContextTransport('unit-test-secret'),
            new AllowAllChildDatatableAuthorizationChecker(),
        )->resolve(new DatatableDefinition('orders'), [], 42, 'orders');
    }

    private function createResolver(
        DatatableContextTransport $transport,
        ChildDatatableAuthorizationCheckerInterface $authorizationChecker,
    ): ChildDatatableResolver {
        return new ChildDatatableResolver(
            new ChildDatatableContextResolver(new RowValueAccessor()),
            new ChildDatatableInstanceFactory(),
            $transport,
            $authorizationChecker,
        );
    }
}

final class CapturingChildDatatableAuthorizationChecker implements ChildDatatableAuthorizationCheckerInterface
{
    public ?ChildDatatableAuthorizationContext $context = null;

    public function isGranted(ChildDatatableAuthorizationContext $context): bool
    {
        $this->context = $context;

        return true;
    }
}
