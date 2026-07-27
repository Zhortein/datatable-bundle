<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\EnumPresentation\EnumPresentation;

interface EnumPresentationResolverInterface
{
    /**
     * @param class-string<\UnitEnum>|null        $enumClass
     * @param array<int|string, EnumPresentation> $presentations
     */
    public function resolve(
        mixed $value,
        ?string $enumClass = null,
        array $presentations = [],
        ?string $translationDomain = null,
    ): ?EnumPresentation;

    /**
     * @param class-string<\UnitEnum>             $enumClass
     * @param array<int|string, EnumPresentation> $presentations
     *
     * @return array<string, string>
     */
    public function resolveChoices(
        string $enumClass,
        array $presentations = [],
        ?string $translationDomain = null,
    ): array;
}
