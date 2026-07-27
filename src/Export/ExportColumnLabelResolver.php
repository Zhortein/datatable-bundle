<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export;

use Symfony\Contracts\Translation\TranslatorInterface;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

final readonly class ExportColumnLabelResolver
{
    public function __construct(
        private ?TranslatorInterface $translator = null,
    ) {
    }

    public function resolve(
        DatatableDefinition $definition,
        ColumnDefinition $column,
    ): string {
        $label = $column->getLabel();

        if (null === $label) {
            return $column->getName();
        }

        $translationDomain = $definition->getTranslationDomain();

        if (null === $translationDomain || null === $this->translator) {
            return $label;
        }

        return $this->translator->trans($label, [], $translationDomain);
    }
}
