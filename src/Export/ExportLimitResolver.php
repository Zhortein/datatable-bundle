<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;

final readonly class ExportLimitResolver
{
    /**
     * @param array<string, int|null> $formatLimits
     */
    public function __construct(
        private int $maxRows = 10000,
        private array $formatLimits = [],
    ) {
        if ($this->maxRows < 1) {
            throw new \InvalidArgumentException('The global export row limit must be greater than or equal to 1.');
        }

        foreach ($this->formatLimits as $format => $limit) {
            ExportFormat::fromString($format);

            if (null !== $limit && $limit < 1) {
                throw new \InvalidArgumentException(sprintf('The "%s" export row limit must be greater than or equal to 1.', $format));
            }
        }
    }

    public function resolve(DatatableDefinition $definition, ExportFormat $format): int
    {
        $definitionLimit = $definition->getExportLimit($format);

        if (null !== $definitionLimit) {
            return $definitionLimit;
        }

        return $this->formatLimits[$format->value] ?? $this->maxRows;
    }
}
