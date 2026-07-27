<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class SmokeOrderLine
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\Column]
        private int $orderId,
        #[ORM\Column(length: 100)]
        private string $product,
        #[ORM\Column]
        private int $quantity,
    ) {
    }
}
