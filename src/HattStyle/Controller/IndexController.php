<?php

namespace App\HattStyle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController {
    #[Route('/', host: 'hatt.style')]
    #[Route('/hatt.style', host: 'localhost')]
    public function index(
        Request $request,
        LoggerInterface $logger,
    ): Response {
        $text = <<<'ZZZZZZZZZZ'
            <h1>hatt.style</h1>
            ZZZZZZZZZZ;
        return new Response("{$text}");
    }
}
