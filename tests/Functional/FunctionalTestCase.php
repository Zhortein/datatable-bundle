<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class FunctionalTestCase extends KernelTestCase
{
    private mixed $initialExceptionHandler = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initialExceptionHandler = self::peekExceptionHandler();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->restoreExceptionHandlerStack();

        $this->initialExceptionHandler = null;
    }

    private static function peekExceptionHandler(): mixed
    {
        $probe = static function (\Throwable $throwable): never {
            throw $throwable;
        };

        $handler = set_exception_handler($probe);
        restore_exception_handler();

        return $handler;
    }

    private function restoreExceptionHandlerStack(): void
    {
        for ($attempt = 0; $attempt < 10; ++$attempt) {
            $currentHandler = self::peekExceptionHandler();

            if ($currentHandler === $this->initialExceptionHandler) {
                return;
            }

            if (null === $currentHandler) {
                return;
            }

            restore_exception_handler();
        }
    }
}
