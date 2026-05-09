<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final readonly class DatatableController
{
    public function __construct(
        private DatatableDefinitionFactory $definitionFactory,
        private DatatableRenderer $renderer,
    ) {
    }

    public function fragments(string $name): JsonResponse
    {
        $definition = $this->definitionFactory->create($name);

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
