<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldTypeGuesser;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUserStatus;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DoctrineFieldTypeGuesserFunctionalTest extends FunctionalTestCase
{
    public function test_it_guesses_string_field_type(): void
    {
        $guesser = $this->getGuesser();

        $fieldType = $guesser->guess(DoctrineUser::class, 'email');

        self::assertSame('email', $fieldType->getFieldName());
        self::assertSame('string', $fieldType->getDoctrineType());
        self::assertSame('string', $fieldType->getCellType());
        self::assertTrue($fieldType->isSearchable());
        self::assertTrue($fieldType->isSortable());
        self::assertFalse($fieldType->isEnum());
        self::assertNull($fieldType->getEnumClass());
    }

    public function test_it_guesses_boolean_field_type(): void
    {
        $guesser = $this->getGuesser();

        $fieldType = $guesser->guess(DoctrineUser::class, 'enabled');

        self::assertSame('enabled', $fieldType->getFieldName());
        self::assertSame('boolean', $fieldType->getDoctrineType());
        self::assertSame('boolean', $fieldType->getCellType());
        self::assertFalse($fieldType->isSearchable());
        self::assertTrue($fieldType->isSortable());
    }

    public function test_it_guesses_datetime_field_type(): void
    {
        $guesser = $this->getGuesser();

        $fieldType = $guesser->guess(DoctrineUser::class, 'createdAt');

        self::assertSame('createdAt', $fieldType->getFieldName());
        self::assertSame('datetime_immutable', $fieldType->getDoctrineType());
        self::assertSame('datetime', $fieldType->getCellType());
        self::assertFalse($fieldType->isSearchable());
        self::assertTrue($fieldType->isSortable());
    }

    public function test_it_guesses_backed_enum_field_type(): void
    {
        $fieldType = $this->getGuesser()->guess(DoctrineUser::class, 'status');

        self::assertSame('status', $fieldType->getFieldName());
        self::assertSame('string', $fieldType->getDoctrineType());
        self::assertSame('enum', $fieldType->getCellType());
        self::assertTrue($fieldType->isSearchable());
        self::assertTrue($fieldType->isSortable());
        self::assertTrue($fieldType->isEnum());
        self::assertSame(DoctrineUserStatus::class, $fieldType->getEnumClass());
    }

    public function test_it_throws_for_unknown_field(): void
    {
        $guesser = $this->getGuesser();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Field "unknown" does not exist on Doctrine entity "%s".',
            DoctrineUser::class,
        ));

        $guesser->guess(DoctrineUser::class, 'unknown');
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    private function getGuesser(): DoctrineFieldTypeGuesser
    {
        self::bootKernel();

        $guesser = self::getContainer()->get('test.'.DoctrineFieldTypeGuesser::class);

        self::assertInstanceOf(DoctrineFieldTypeGuesser::class, $guesser);

        return $guesser;
    }
}
