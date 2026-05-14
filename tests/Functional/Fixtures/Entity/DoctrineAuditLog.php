<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class DoctrineAuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(type: 'string', length: 180)]
        private string $className,
        #[ORM\Column(type: 'integer')]
        private int $objectId,
        #[ORM\Column(type: 'string', length: 120)]
        private string $eventName,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }
}
