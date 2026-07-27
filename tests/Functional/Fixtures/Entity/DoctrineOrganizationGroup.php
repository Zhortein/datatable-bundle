<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class DoctrineOrganizationGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(type: 'string', length: 120)]
        private string $name,
        #[ORM\Column(type: 'string', enumType: DoctrineUserStatus::class)]
        private DoctrineUserStatus $status = DoctrineUserStatus::Active,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): DoctrineUserStatus
    {
        return $this->status;
    }
}
