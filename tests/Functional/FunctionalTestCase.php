<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional;

use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class FunctionalTestCase extends KernelTestCase
{
    /**
     * @param array<mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        $options['debug'] = false;

        return parent::createKernel($options);
    }

    /**
     * Asserts that exactly one query the default connection executed since the previous check matches the pattern.
     */
    protected static function assertQueryExecuted(string $pattern, string $message = ''): void
    {
        $debugDataHolder = self::getContainer()->get('doctrine.debug_data_holder');
        self::assertInstanceOf(DebugDataHolder::class, $debugDataHolder);
        $queries = $debugDataHolder->getData()['default'] ?? [];
        $debugDataHolder->reset();
        self::assertIsArray($queries);

        $matching = array_filter(
            array_column($queries, 'sql'),
            static fn (mixed $sql): bool => is_string($sql) && 1 === preg_match($pattern, $sql),
        );

        self::assertCount(1, $matching, $message);
    }
}
