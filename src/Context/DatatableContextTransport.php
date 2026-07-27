<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Context;

use Zhortein\DatatableBundle\Exception\InvalidDatatableContextException;

/**
 * Signs the explicitly browser-safe part of a datatable context.
 */
final readonly class DatatableContextTransport
{
    public const string CONTEXT_QUERY_PARAMETER = '_zd_context';
    public const string INSTANCE_QUERY_PARAMETER = '_zd_instance';
    private const int TOKEN_VERSION = 1;
    private const int MAX_TOKEN_LENGTH = 8192;

    public function __construct(
        private string $secret,
    ) {
    }

    public function createToken(string $datatableName, string $instance, DatatableContext $context): ?string
    {
        $values = $this->normalizeBrowserValues($context->getBrowserSafeValues());

        if ([] === $values) {
            return null;
        }

        $payload = json_encode([
            'version' => self::TOKEN_VERSION,
            'datatable' => $datatableName,
            'instance' => $this->normalizeInstance($instance),
            'values' => $values,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $encodedPayload = $this->base64UrlEncode($payload);
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, $this->getSecret(), true));

        return $encodedPayload.'.'.$signature;
    }

    public function restore(
        string $token,
        string $datatableName,
        string $instance,
        DatatableContext $context,
    ): DatatableContext {
        if ('' === $token || self::MAX_TOKEN_LENGTH < strlen($token)) {
            throw new InvalidDatatableContextException('The datatable context token has an invalid length.');
        }

        $tokenParts = explode('.', $token);

        if (2 !== count($tokenParts)) {
            throw new InvalidDatatableContextException('The datatable context token has an invalid format.');
        }

        [$encodedPayload, $encodedSignature] = $tokenParts;
        $expectedSignature = hash_hmac('sha256', $encodedPayload, $this->getSecret(), true);
        $signature = $this->base64UrlDecode($encodedSignature);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new InvalidDatatableContextException('The datatable context token signature is invalid.');
        }

        try {
            $payload = json_decode(
                $this->base64UrlDecode($encodedPayload),
                true,
                8,
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $exception) {
            throw new InvalidDatatableContextException('The datatable context token payload is invalid.', previous: $exception);
        }

        if (!is_array($payload)) {
            throw new InvalidDatatableContextException('The datatable context token payload must be an object.');
        }

        if (
            self::TOKEN_VERSION !== ($payload['version'] ?? null)
            || $datatableName !== ($payload['datatable'] ?? null)
            || $this->normalizeInstance($instance) !== ($payload['instance'] ?? null)
            || !is_array($payload['values'] ?? null)
        ) {
            throw new InvalidDatatableContextException('The datatable context token does not match this datatable instance.');
        }

        try {
            return $context->withBrowserValues(
                $this->normalizeBrowserValues($payload['values']),
            );
        } catch (\InvalidArgumentException $exception) {
            throw new InvalidDatatableContextException('The datatable context token contains a forbidden key.', previous: $exception);
        }
    }

    public function appendToUrl(string $url, ?string $token, string $instance): string
    {
        if (null === $token) {
            return $url;
        }

        [$urlWithoutFragment, $fragment] = $this->splitOnce($url, '#');
        [$path, $query] = $this->splitOnce($urlWithoutFragment, '?');
        $queryParts = [];

        if ('' !== $query) {
            foreach (explode('&', $query) as $queryPart) {
                if ('' === $queryPart) {
                    continue;
                }

                $parameterName = urldecode(explode('=', $queryPart, 2)[0]);

                if (in_array($parameterName, [self::CONTEXT_QUERY_PARAMETER, self::INSTANCE_QUERY_PARAMETER], true)) {
                    continue;
                }

                $queryParts[] = $queryPart;
            }
        }

        $queryParts[] = rawurlencode(self::INSTANCE_QUERY_PARAMETER).'='.rawurlencode($this->normalizeInstance($instance));
        $queryParts[] = rawurlencode(self::CONTEXT_QUERY_PARAMETER).'='.rawurlencode($token);

        return $path.'?'.implode('&', $queryParts).('' === $fragment ? '' : '#'.$fragment);
    }

    public function normalizeInstance(string $instance): string
    {
        $instance = trim($instance);

        if ('' === $instance || 128 < strlen($instance) || 1 === preg_match('/[\x00-\x1F\x7F]/', $instance)) {
            throw new \InvalidArgumentException('A datatable instance key must be a non-empty string of at most 128 characters without control characters.');
        }

        return $instance;
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array<string, bool|float|int|string|null>
     */
    private function normalizeBrowserValues(array $values): array
    {
        if (64 < count($values)) {
            throw new \InvalidArgumentException('A datatable context cannot propagate more than 64 browser-safe values.');
        }

        $normalizedValues = [];

        foreach ($values as $name => $value) {
            if (!is_string($name) || '' === trim($name)) {
                throw new \InvalidArgumentException('A transported datatable context key must be a non-empty string.');
            }

            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            } elseif ($value instanceof \Stringable) {
                $value = (string) $value;
            }

            if (null !== $value && !is_scalar($value)) {
                throw new \InvalidArgumentException(sprintf('The browser-safe datatable context value "%s" must be scalar, null, a backed enum or Stringable; "%s" given.', $name, get_debug_type($value)));
            }

            $normalizedValues[trim($name)] = $value;
        }

        return $normalizedValues;
    }

    private function getSecret(): string
    {
        if ('' === $this->secret) {
            throw new \LogicException('A non-empty kernel secret is required to transport datatable context.');
        }

        return $this->secret;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $decodedValue = base64_decode(strtr($value, '-_', '+/'), true);

        if (false === $decodedValue) {
            throw new InvalidDatatableContextException('The datatable context token contains invalid base64 data.');
        }

        return $decodedValue;
    }

    /**
     * @return array{string, string}
     */
    private function splitOnce(string $value, string $separator): array
    {
        $parts = explode($separator, $value, 2);

        return [$parts[0], $parts[1] ?? ''];
    }
}
