<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\DateTime;

use Symfony\Component\HttpFoundation\RequestStack;

final readonly class DefaultDateTimeFormatter implements DateTimeFormatterInterface
{
    public function __construct(
        private ?RequestStack $requestStack = null,
        private ?string $timezone = null,
        private string $fallbackFormat = 'Y-m-d H:i',
    ) {
    }

    public function format(mixed $value): string
    {
        if (!$value instanceof \DateTimeInterface) {
            return is_scalar($value) ? (string) $value : '';
        }

        $dateTime = \DateTimeImmutable::createFromInterface($value);

        if (null !== $this->timezone && '' !== $this->timezone) {
            $dateTime = $dateTime->setTimezone(new \DateTimeZone($this->timezone));
        }

        if (class_exists(\IntlDateFormatter::class)) {
            $formatted = $this->formatWithIntl($dateTime);

            if (false !== $formatted) {
                return $formatted;
            }
        }

        return $dateTime->format($this->fallbackFormat);
    }

    private function formatWithIntl(\DateTimeInterface $value): false|string
    {
        $formatter = new \IntlDateFormatter(
            $this->getLocale(),
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::SHORT,
            $value->getTimezone()->getName(),
        );

        return $formatter->format($value);
    }

    private function getLocale(): string
    {
        return $this->requestStack?->getCurrentRequest()?->getLocale() ?? 'en';
    }
}
