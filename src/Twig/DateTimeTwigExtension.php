<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Twig;

use Twig\Attribute\AsTwigFunction;
use Zhortein\DatatableBundle\DateTime\DateTimeFormatterInterface;

final readonly class DateTimeTwigExtension
{
    public function __construct(
        private DateTimeFormatterInterface $dateTimeFormatter,
    ) {
    }

    #[AsTwigFunction('zhortein_datatable_datetime')]
    public function formatDateTime(mixed $value): string
    {
        return $this->dateTimeFormatter->format($value);
    }
}
