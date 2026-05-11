<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

final class DatatableDefinitionOptionsTest extends TestCase
{
    public function test_it_stores_definition_options(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->setOption('foo', 'bar');

        self::assertTrue($definition->hasOption('foo'));
        self::assertSame('bar', $definition->getOption('foo'));
        self::assertSame('fallback', $definition->getOption('missing', 'fallback'));
        self::assertSame(['foo' => 'bar'], $definition->getOptions());
    }
}
