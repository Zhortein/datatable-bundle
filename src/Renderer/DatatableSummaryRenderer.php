<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Renderer;

use Symfony\Contracts\Translation\TranslatorInterface;
use Zhortein\DatatableBundle\Result\DatatableResult;

final readonly class DatatableSummaryRenderer
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function render(DatatableResult $result): string
    {
        if (0 === $result->getFilteredItems()) {
            return $this->translator->trans(
                'zhortein_datatable.summary.empty',
                [],
                'zhortein_datatable',
            );
        }

        $start = (($result->getPage() - 1) * $result->getPageSize()) + 1;
        $end = min($result->getPage() * $result->getPageSize(), $result->getFilteredItems());

        if ($result->hasFilteredItems()) {
            return $this->translator->trans(
                1 === $result->getFilteredItems()
                    ? 'zhortein_datatable.summary.filtered_single'
                    : 'zhortein_datatable.summary.filtered_multiple',
                [
                    '%start%' => $start,
                    '%end%' => $end,
                    '%filtered%' => $result->getFilteredItems(),
                    '%total%' => $result->getTotalItems(),
                ],
                'zhortein_datatable',
            );
        }

        return $this->translator->trans(
            1 === $result->getFilteredItems()
                ? 'zhortein_datatable.summary.single'
                : 'zhortein_datatable.summary.multiple',
            [
                '%start%' => $start,
                '%end%' => $end,
                '%total%' => $result->getFilteredItems(),
            ],
            'zhortein_datatable',
        );
    }
}
