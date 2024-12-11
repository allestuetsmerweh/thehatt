<?php

namespace App\Flatastic\Controller;

use App\Flatastic\Service\FlatasticApi;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ShoutController extends AbstractController {
    #[Route('/shouts/create', host: 'flatastic.hatt.style')]
    #[Route('/flatastic.hatt.style/shouts/create', host: 'localhost')]
    public function create(
        Request $request,
        FlatasticApi $api,
        LoggerInterface $logger,
    ): JsonResponse {
        $shout = $request->query->get('shout');
        if (!$shout) {
            throw new \Exception('NOT SHOUTING ANYTHING!!!');
        }

        $auth_data = $api->authenticateFromRequest($request);

        $shout = $api->createShout($shout);

        return new JsonResponse([
            'shout' => $shout,
        ]);
    }
}
