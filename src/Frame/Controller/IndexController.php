<?php

namespace App\Frame\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    public $frame_path = __DIR__.'/../../../public/frame/';

    #[Route('/', host:'frame.hatt.style')]
    #[Route('/frame.hatt.style', host:'localhost')]
    public function index(
        Request $request,
        LoggerInterface $logger,
    ): Response {
        $query = $request->getPathInfo();

        if (isset($_FILES['picture'])) {
            $this->setupFilesystem();
            $img = new \Imagick();
            $img->readImage($_FILES['picture']['tmp_name']);
            $img->resizeImage(800, 480, \Imagick::FILTER_LANCZOS, 1);
            $hash = hash('sha256', \openssl_random_pseudo_bytes(4));
            $img->writeImage("{$this->frame_path}{$hash}.jpg");
            $img->destroy(); 
        }
        $pictures = $this->getPictures();
        $pictures_html = '';
        foreach ($pictures as $picture) {
            $pictures_html .= "<div><img src='{$query}frame/{$picture}' alt='(None)'/></div>";
        }
        $text = <<<ZZZZZZZZZZ
        <h1>frame.hatt.style</h1>
        {$pictures_html}
        <form action='{$query}' method='post' enctype='multipart/form-data'>
            <div><input type='file' name='picture' accept='image/png, image/jpeg'/></div>
            <div><input type='submit' value='Neues Bild'/></div>
        </form>
        ZZZZZZZZZZ;
        return new Response("$text");
    }

    #[Route('/frame/{picture}.jpg', host:'frame.hatt.style')]
    #[Route('/frame.hatt.style/frame/{picture}.jpg', host:'localhost')]
    public function specific(
        Request $request,
        LoggerInterface $logger,
        string $picture,
    ): BinaryFileResponse {
        $this->setupFilesystem();
        return new BinaryFileResponse("{$this->frame_path}{$picture}.jpg");
    }

    #[Route('/frame.jpg', host:'frame.hatt.style')]
    #[Route('/frame.hatt.style/frame.jpg', host:'localhost')]
    public function random(
        Request $request,
        LoggerInterface $logger,
    ): Response {
        $pictures = $this->getPictures();
        $index = random_int(0, count($pictures) - 1);
        $picture = $pictures[$index];
        $img = new \Imagick("{$this->frame_path}{$picture}");
        $img->modulateImage(100.0, 150.0, 100.0);
        $blob = $img->getImageBlob();
        $img->destroy(); 
        return new Response($blob, 200, ['Content-Type' => 'image/jpeg']);
    }

    /** @return array<string> */
    protected function getPictures(): array {
        $this->setupFilesystem();
        $entries = scandir($this->frame_path);
        $pictures = [];
        foreach ($entries as $entry) {
            if (substr($entry, 0, 1) !== '.') {
                $pictures[] = $entry;
            }
        }
        return $pictures;
    }

    protected function setupFilesystem(): void {
        if (!is_dir($this->frame_path)) {
            mkdir($this->frame_path);
        }
    }
}