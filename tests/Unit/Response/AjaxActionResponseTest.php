<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Response;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Response\AjaxActionResponse;

final class AjaxActionResponseTest extends TestCase
{
    public function test_it_creates_a_versioned_success_response(): void
    {
        $response = AjaxActionResponse::success('User archived.');

        self::assertSame(200, $response->getStatusCode());
        self::assertResponsePayloadSame([
            'version' => 1,
            'ok' => true,
            'message' => 'User archived.',
            'errors' => [],
            'redirect' => null,
        ], $response);
    }

    public function test_it_creates_a_versioned_redirect_response(): void
    {
        $response = AjaxActionResponse::redirect('/users/42', 'User created.');

        self::assertResponsePayloadSame([
            'version' => 1,
            'ok' => true,
            'message' => 'User created.',
            'errors' => [],
            'redirect' => '/users/42',
        ], $response);
    }

    public function test_it_creates_a_versioned_failure_response(): void
    {
        $response = AjaxActionResponse::failure(
            message: 'The user cannot be archived.',
            errors: [
                [
                    'message' => 'The account is protected.',
                    'code' => 'protected_account',
                    'field' => 'account',
                ],
            ],
            status: 409,
        );

        self::assertSame(409, $response->getStatusCode());
        self::assertResponsePayloadSame([
            'version' => 1,
            'ok' => false,
            'message' => 'The user cannot be archived.',
            'errors' => [
                [
                    'message' => 'The account is protected.',
                    'code' => 'protected_account',
                    'field' => 'account',
                ],
            ],
            'redirect' => null,
        ], $response);
    }

    /**
     * @param callable(): AjaxActionResponse $factory
     */
    #[DataProvider('provideInvalidResponses')]
    public function test_it_rejects_incoherent_responses(callable $factory, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $factory();
    }

    /**
     * @return iterable<string, array{0: callable(): AjaxActionResponse, 1: string}>
     */
    public static function provideInvalidResponses(): iterable
    {
        yield 'success with an error status' => [
            static fn (): AjaxActionResponse => AjaxActionResponse::success(status: 422),
            'A successful Ajax action response requires an HTTP status between 200 and 299, 422 given.',
        ];

        yield 'failure with a success status' => [
            static fn (): AjaxActionResponse => AjaxActionResponse::failure(status: 200),
            'A failed Ajax action response requires an HTTP status between 400 and 599, 200 given.',
        ];

        yield 'empty redirect' => [
            static fn (): AjaxActionResponse => AjaxActionResponse::redirect(' '),
            'An Ajax action redirect URL cannot be empty.',
        ];
    }

    /**
     * @param array<string, mixed> $expected
     */
    private static function assertResponsePayloadSame(array $expected, AjaxActionResponse $response): void
    {
        $content = $response->getContent();

        self::assertIsString($content);
        self::assertSame($expected, json_decode($content, true, flags: JSON_THROW_ON_ERROR));
    }
}
