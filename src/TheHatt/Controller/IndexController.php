<?php

namespace App\TheHatt\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController {
    #[Route('/')]
    public function index(
        Request $request,
        LoggerInterface $logger,
    ): Response {
        $text = <<<'ZZZZZZZZZZ'
            <h1>thehatt.ch</h1>
            ZZZZZZZZZZ;
        return new Response("{$text}");
    }
}
