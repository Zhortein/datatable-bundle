<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineOrganization;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;

trait DoctrineSchemaMetadataTrait
{
    /**
     * @return list<ClassMetadata<object>>
     */
    private function getMetadata(): array
    {
        $entityManager = $this->getStoredEntityManager();

        return [
            $entityManager->getClassMetadata(DoctrineOrganization::class),
            $entityManager->getClassMetadata(DoctrineUser::class),
        ];
    }

    abstract private function getStoredEntityManager(): EntityManagerInterface;
}
