<?php

namespace App\Flatastic\Controller;

use App\Flatastic\Service\FlatasticApi;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ShoppingListController extends AbstractController {
    #[Route('/shopping_list', host: 'flatastic.hatt.style')]
    #[Route('/flatastic.hatt.style/shopping_list', host: 'localhost')]
    public function list(
        Request $request,
        FlatasticApi $api,
        LoggerInterface $logger,
    ): JsonResponse {
        $auth_data = $api->authenticateFromRequest($request);

        $flatmates = $api->getFlatmates();
        $shopping_list = $api->getShoppingList();

        return new JsonResponse([
            'flatmates' => $flatmates,
            'shopping_list' => $shopping_list,
        ]);
    }
}
