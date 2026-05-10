<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export;

use Zhortein\DatatableBundle\Contract\ExportWriterInterface;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Exception\ExportWriterNotFoundException;

final readonly class ExportWriterRegistry
{
    /**
     * @var array<string, ExportWriterInterface>
     */
    private array $writers;

    /**
     * @param iterable<string, ExportWriterInterface> $writers
     */
    public function __construct(iterable $writers = [])
    {
        $normalizedWriters = [];

        foreach ($writers as $name => $writer) {
            $name = trim($name);

            if ('' === $name) {
                throw new ExportWriterNotFoundException('An export writer cannot be registered with an empty name.');
            }

            $normalizedWriters[$name] = $writer;
        }

        $this->writers = $normalizedWriters;
    }

    public function has(string $name): bool
    {
        return isset($this->writers[$name]);
    }

    public function get(string $name): ExportWriterInterface
    {
        if (!$this->has($name)) {
            throw new ExportWriterNotFoundException(sprintf('The export writer "%s" is not registered.', $name));
        }

        return $this->writers[$name];
    }

    public function resolve(ExportFormat $format): ExportWriterInterface
    {
        foreach ($this->writers as $writer) {
            if ($writer->supports($format)) {
                return $writer;
            }
        }

        throw new ExportWriterNotFoundException(sprintf('No export writer supports format "%s".', $format->value));
    }

    /**
     * @return list<string>
     */
    public function getNames(): array
    {
        return array_keys($this->writers);
    }
}
