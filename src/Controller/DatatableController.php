<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final readonly class DatatableController
{
    public function __construct(
        private DatatableRegistry $registry,
        private DatatableRenderer $renderer,
    ) {
    }

    public function fragments(string $name): JsonResponse
    {
        $datatable = $this->registry->get($name);
        $definition = new DatatableDefinition($name);

        $datatable->buildDatatable($definition);

        return new JsonResponse([
            'body' => $this->renderer->renderEmptyBody($definition),
            'pagination' => $this->renderer->renderPaginationPlaceholder($definition),
            'summary' => '',
            'page' => 1,
            'pageSize' => 0,
            'totalItems' => 0,
            'totalPages' => 0,
        ], Response::HTTP_OK);
    }
}
