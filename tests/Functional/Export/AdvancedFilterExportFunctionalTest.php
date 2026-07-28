<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Export;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Zhortein\DatatableBundle\Controller\DatatableController;
use Zhortein\DatatableBundle\Tests\Functional\Doctrine\DoctrineSchemaMetadataTrait;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineOrganization;
use Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity\DoctrineUser;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class AdvancedFilterExportFunctionalTest extends FunctionalTestCase
{
    use DoctrineSchemaMetadataTrait;

    private ?EntityManagerInterface $entityManager = null;

    public function test_csv_export_respects_advanced_filters(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        // Advanced filter for Alice only
        $advancedFilters = [
            'logic' => 'AND',
            'children' => [
                [
                    'field' => 'email',
                    'operator' => 'eq',
                    'value' => 'alice@example.test',
                ],
            ],
        ];

        $request = new Request(query: [
            'advancedFilters' => $advancedFilters,
        ]);

        $controller = self::getContainer()->get(DatatableController::class);
        self::assertInstanceOf(DatatableController::class, $controller);

        $response = $controller->export($request, 'doctrine-users', 'csv');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));

        $content = $this->getResponseContent($response);
        self::assertStringContainsString('alice@example.test', $content);
        self::assertStringNotContainsString('bob@example.test', $content);
        self::assertStringNotContainsString('charlie@example.test', $content);
    }

    public function test_xlsx_export_respects_advanced_filters(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        // Advanced filter for enabled users only (Alice and Charlie)
        $advancedFilters = [
            'logic' => 'AND',
            'children' => [
                [
                    'field' => 'enabled',
                    'operator' => 'eq',
                    'value' => true,
                ],
            ],
        ];

        $request = new Request(query: [
            'advancedFilters' => $advancedFilters,
        ]);

        $controller = self::getContainer()->get(DatatableController::class);
        self::assertInstanceOf(DatatableController::class, $controller);

        $response = $controller->export($request, 'doctrine-users', 'xlsx');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));

        self::assertNotEmpty($this->getResponseContent($response));
    }

    public function test_array_export_respects_advanced_filters(): void
    {
        self::bootKernel();

        $advancedFilters = [
            'logic' => 'AND',
            'children' => [
                [
                    'field' => 'email',
                    'operator' => 'eq',
                    'value' => 'alice@example.test',
                ],
            ],
        ];

        $request = new Request(query: [
            'advancedFilters' => $advancedFilters,
        ]);

        $controller = self::getContainer()->get(DatatableController::class);
        self::assertInstanceOf(DatatableController::class, $controller);

        $response = $controller->export($request, 'array-users', 'csv');

        self::assertSame(200, $response->getStatusCode());

        $content = $this->getResponseContent($response);
        self::assertStringContainsString('alice@example.test', $content);
        self::assertStringNotContainsString('bob@example.test', $content);
    }

    public function test_full_mode_export_respects_advanced_filters(): void
    {
        $this->bootDoctrineAndLoadFixtures();

        // Advanced filter for Bob only
        $advancedFilters = [
            'logic' => 'AND',
            'children' => [
                [
                    'field' => 'email',
                    'operator' => 'eq',
                    'value' => 'bob@example.test',
                ],
            ],
        ];

        $request = new Request(query: [
            'advancedFilters' => $advancedFilters,
            'mode' => 'full',
        ]);

        $controller = self::getContainer()->get(DatatableController::class);
        self::assertInstanceOf(DatatableController::class, $controller);

        $response = $controller->export($request, 'doctrine-users', 'csv');

        self::assertSame(200, $response->getStatusCode());

        $content = $this->getResponseContent($response);
        self::assertStringContainsString('bob@example.test', $content);
        self::assertStringNotContainsString('alice@example.test', $content);
        self::assertStringNotContainsString('charlie@example.test', $content);
    }

    #[After]
    protected function cleanupDoctrine(): void
    {
        $entityManager = $this->entityManager;

        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $this->dropSchema();
        $entityManager->close();
        $this->entityManager = null;
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    private function bootDoctrineAndLoadFixtures(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $this->entityManager = $entityManager;
        $this->recreateSchema();

        $organization = new DoctrineOrganization('Acme Corp', true);
        $entityManager->persist($organization);

        $entityManager->persist(new DoctrineUser(
            email: 'alice@example.test',
            displayName: 'Alice',
            enabled: true,
            organization: $organization,
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'bob@example.test',
            displayName: 'Bob',
            enabled: false,
            organization: $organization,
        ));
        $entityManager->persist(new DoctrineUser(
            email: 'charlie@example.test',
            displayName: 'Charlie',
            enabled: true,
            organization: $organization,
        ));

        $entityManager->flush();
        $entityManager->clear();
    }

    private function recreateSchema(): void
    {
        $this->dropSchema();

        $entityManager = $this->getStoredEntityManager();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema($this->getMetadata());
    }

    private function dropSchema(): void
    {
        $entityManager = $this->entityManager;

        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $schemaTool = new SchemaTool($entityManager);

        try {
            $schemaTool->dropSchema($this->getMetadata());
        } catch (\Throwable) {
            // The schema may not exist yet.
        }
    }

    private function getStoredEntityManager(): EntityManagerInterface
    {
        if (!$this->entityManager instanceof EntityManagerInterface) {
            throw new \LogicException('The entity manager is not initialized.');
        }

        return $this->entityManager;
    }

    private function getResponseContent(Response $response): string
    {
        if (!$response instanceof StreamedResponse) {
            $content = $response->getContent();

            self::assertIsString($content);

            return $content;
        }

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        self::assertIsString($content);

        return $content;
    }
}
