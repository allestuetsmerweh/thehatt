<?php

namespace App\Flatastic\Controller;

use App\Flatastic\Service\FlatasticApi;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class BotController extends AbstractController
{
    #[Route('/bot', host:'flatastic.hatt.style')]
    #[Route('/flatastic.hatt.style/bot', host:'localhost')]
    public function list(
        Request $request,
        FlatasticApi $api,
        LoggerInterface $logger,
    ): JsonResponse {
        $auth_data = $api->authenticateFromRequest($request);

        $bot = $api->getBotUser();

        return new JsonResponse([
            'bot' => $bot,
        ]);
    }
}