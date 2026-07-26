<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Enum\AjaxActionSuccessStrategy;

final readonly class AjaxActionOptions
{
    public function __construct(
        private AjaxActionSuccessStrategy $successStrategy = AjaxActionSuccessStrategy::RefreshTable,
    ) {
    }

    public function getSuccessStrategy(): AjaxActionSuccessStrategy
    {
        return $this->successStrategy;
    }
}
