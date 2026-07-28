<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

use Zhortein\DatatableBundle\Contract\ExportJobIdentifierGeneratorInterface;

final class RandomExportJobIdentifierGenerator implements ExportJobIdentifierGeneratorInterface
{
    public function generate(): ExportJobIdentifier
    {
        return new ExportJobIdentifier(rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '='));
    }
}
