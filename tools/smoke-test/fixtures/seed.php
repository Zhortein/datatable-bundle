<?php

declare(strict_types=1);

use App\Entity\SmokeOrderLine;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

require __DIR__.'/vendor/autoload.php';

$kernel = new App\Kernel('smoke_complete', true);
$kernel->boot();

$registry = $kernel->getContainer()->get('doctrine');
$entityManager = $registry->getManagerForClass(SmokeOrderLine::class);

if (!$entityManager instanceof EntityManagerInterface) {
    throw new RuntimeException('The smoke order line entity manager is unavailable.');
}

$metadata = $entityManager->getClassMetadata(SmokeOrderLine::class);
new SchemaTool($entityManager)->createSchema([$metadata]);

foreach ([
    new SmokeOrderLine(1, 101, 'Mechanical keyboard', 2),
    new SmokeOrderLine(2, 101, 'Wireless mouse', 1),
    new SmokeOrderLine(3, 102, 'External SSD', 3),
] as $orderLine) {
    $entityManager->persist($orderLine);
}

$entityManager->flush();
$kernel->shutdown();
