<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'doctrine_organizations')]
class DoctrineOrganization
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180)]
    private string $name;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled;

    #[ORM\Column(type: 'string', enumType: DoctrineUserStatus::class)]
    private DoctrineUserStatus $status;

    #[ORM\ManyToOne(targetEntity: DoctrineOrganizationGroup::class)]
    private ?DoctrineOrganizationGroup $group = null;

    /** @var Collection<int, DoctrineUser> */
    #[ORM\OneToMany(targetEntity: DoctrineUser::class, mappedBy: 'organization')]
    private Collection $users;

    public function __construct(
        string $name,
        bool $enabled = true,
        ?DoctrineOrganizationGroup $group = null,
        DoctrineUserStatus $status = DoctrineUserStatus::Active,
    ) {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->group = $group;
        $this->status = $status;
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getGroup(): ?DoctrineOrganizationGroup
    {
        return $this->group;
    }

    public function getStatus(): DoctrineUserStatus
    {
        return $this->status;
    }
}
