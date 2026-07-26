<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

final class AjaxActionResponse extends JsonResponse
{
    public const int VERSION = 1;

    /**
     * @param list<array{message: string, code?: string, field?: string}> $errors
     */
    private function __construct(
        bool $ok,
        ?string $message,
        array $errors,
        ?string $redirect,
        int $status,
    ) {
        parent::__construct([
            'version' => self::VERSION,
            'ok' => $ok,
            'message' => $message,
            'errors' => $errors,
            'redirect' => $redirect,
        ], $status);
    }

    public static function success(?string $message = null, int $status = self::HTTP_OK): self
    {
        self::assertStatusRange($status, self::HTTP_OK, 299, 'successful');

        return new self(
            ok: true,
            message: $message,
            errors: [],
            redirect: null,
            status: $status,
        );
    }

    public static function redirect(string $url, ?string $message = null, int $status = self::HTTP_OK): self
    {
        self::assertStatusRange($status, self::HTTP_OK, 299, 'successful');

        if ('' === trim($url)) {
            throw new \InvalidArgumentException('An Ajax action redirect URL cannot be empty.');
        }

        return new self(
            ok: true,
            message: $message,
            errors: [],
            redirect: $url,
            status: $status,
        );
    }

    /**
     * @param list<array{message: string, code?: string, field?: string}> $errors
     */
    public static function failure(
        ?string $message = null,
        array $errors = [],
        int $status = self::HTTP_UNPROCESSABLE_ENTITY,
    ): self {
        self::assertStatusRange($status, self::HTTP_BAD_REQUEST, 599, 'failed');

        return new self(
            ok: false,
            message: $message,
            errors: $errors,
            redirect: null,
            status: $status,
        );
    }

    private static function assertStatusRange(int $status, int $minimum, int $maximum, string $responseType): void
    {
        if ($status < $minimum || $status > $maximum) {
            throw new \InvalidArgumentException(sprintf(
                'A %s Ajax action response requires an HTTP status between %d and %d, %d given.',
                $responseType,
                $minimum,
                $maximum,
                $status,
            ));
        }
    }
}
