<?php

namespace App\Flatastic\Controller;

use App\Flatastic\Service\FlatasticApi;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class FlatmatesController extends AbstractController
{
    #[Route('/flatmates', host:'flatastic.hatt.style')]
    #[Route('/flatastic.hatt.style/flatmates', host:'localhost')]
    public function index(
        Request $request,
        FlatasticApi $api,
        LoggerInterface $logger,
    ): JsonResponse {
        $auth_data = $api->authenticateFromRequest($request);

        $flatmates = $api->getFlatmates();
        $chores_stats = $api->getChoresStats();
        foreach ($chores_stats['chore'] as $id => $value) {
            $flatmate = $flatmates[strval($id)] ?? [];
            $flatmate['choreStats'] = $value;
            $flatmates[strval($id)] = $flatmate;
        }
        foreach ($chores_stats['shout'] as $id => $value) {
            $flatmate = $flatmates[strval($id)] ?? [];
            $flatmate['shoutStats'] = $value;
            $flatmates[strval($id)] = $flatmate;
        }

        return new JsonResponse($flatmates);
    }
}
