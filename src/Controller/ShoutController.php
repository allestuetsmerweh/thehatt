<?php

namespace App\Controller;

use App\Service\FlatasticApi;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ShoutController extends AbstractController
{
    #[Route('/shouts/create')]
    public function create(
        Request $request,
        FlatasticApi $api,
        LoggerInterface $logger,
    ): JsonResponse {
        $shout = $request->query->get('shout');
        if (!$shout) {
            throw new Exception('NOT SHOUTING ANYTHING!!!');
        }

        $auth_data = $api->authenticateFromRequest($request);

        $shout = $api->createShout($shout);

        return new JsonResponse([
            'shout' => $shout,
        ]);
    }
}