<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'doctrine_users')]
class DoctrineUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email;

    #[ORM\Column(type: 'string', length: 120)]
    private string $displayName;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: DoctrineOrganization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?DoctrineOrganization $organization;

    public function __construct(
        string $email,
        string $displayName,
        bool $enabled = true,
        ?\DateTimeImmutable $createdAt = null,
        ?DoctrineOrganization $organization = null,
    ) {
        $this->email = $email;
        $this->displayName = $displayName;
        $this->enabled = $enabled;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->organization = $organization;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getOrganization(): ?DoctrineOrganization
    {
        return $this->organization;
    }
}
