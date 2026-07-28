<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Export\Job\ExportJobIdentifier;

interface ExportJobIdentifierGeneratorInterface
{
    public function generate(): ExportJobIdentifier;
}
