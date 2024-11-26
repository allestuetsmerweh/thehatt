<?php

namespace App\Flatastic\Controller;

use App\Flatastic\Service\FlatasticApi;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class FlatController extends AbstractController
{
    #[Route('/flat', host:'flatastic.hatt.style')]
    #[Route('/flatastic.hatt.style/flat', host:'localhost')]
    public function list(
        Request $request,
        FlatasticApi $api,
        LoggerInterface $logger,
    ): JsonResponse {
        $auth_data = $api->authenticateFromRequest($request);

        $flat = $api->getFlat();

        return new JsonResponse([
            'flat' => $flat,
        ]);
    }
}