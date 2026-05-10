<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Action;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Action\ActionVisibilityContext;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

final class ActionVisibilityContextTest extends TestCase
{
    public function test_it_stores_visibility_context_without_row(): void
    {
        $definition = new DatatableDefinition('users');

        $context = new ActionVisibilityContext(
            definition: $definition,
            options: ['foo' => 'bar'],
        );

        self::assertSame($definition, $context->getDefinition());
        self::assertNull($context->getRow());
        self::assertFalse($context->hasRow());
        self::assertSame(['foo' => 'bar'], $context->getOptions());
        self::assertSame('bar', $context->getOption('foo'));
        self::assertSame('fallback', $context->getOption('missing', 'fallback'));
    }

    public function test_it_stores_visibility_context_with_row(): void
    {
        $row = [
            'e_id' => 42,
            'e_email' => 'alice@example.test',
        ];

        $context = new ActionVisibilityContext(
            definition: new DatatableDefinition('users'),
            row: $row,
        );

        self::assertSame($row, $context->getRow());
        self::assertTrue($context->hasRow());
    }
}
