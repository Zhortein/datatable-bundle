<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class SmokeController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('smoke.html.twig', [
            'hierarchyEnabled' => class_exists('App\Datatable\SmokeOrderDatatable'),
        ]);
    }
}
