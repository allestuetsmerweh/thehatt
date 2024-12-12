<?php

namespace App\Frame\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController {
    #[Route('/', host: 'frame.hatt.style')]
    #[Route('/frame.hatt.style', host: 'localhost')]
    #[Route('/frame.hatt.style', host: 'staging.hatt.style')]
    public function index(
        Request $request,
        LoggerInterface $logger,
    ): Response {
        $href = $this->getHref($request);

        if (isset($_FILES['picture'])) {
            $this->setupFilesystem();
            $img = new \Imagick();
            $img->readImage($_FILES['picture']['tmp_name']);
            $img->setImageCompressionQuality(100);
            $img->resizeImage(800, 480, \Imagick::FILTER_LANCZOS, 1);
            $hash = hash('sha256', \openssl_random_pseudo_bytes(4));
            $img->writeImage("{$this->getFramePath()}{$hash}.jpg");
            $img->destroy();
        }
        $pictures = $this->getPictures();
        $pictures_html = '';
        foreach ($pictures as $picture) {
            $pictures_html .= "<div><img src='{$href}/frame/{$picture}' alt='(None)'/></div>";
        }
        $text = <<<ZZZZZZZZZZ
            <h1>frame.hatt.style</h1>
            {$pictures_html}
            <form action='{$href}' method='post' enctype='multipart/form-data'>
                <div><input type='file' name='picture' accept='image/png, image/jpeg'/></div>
                <div><input type='submit' value='Neues Bild'/></div>
            </form>
            ZZZZZZZZZZ;
        return new Response("{$text}");
    }

    #[Route('/frame/{picture}.jpg', host: 'frame.hatt.style')]
    #[Route('/frame.hatt.style/frame/{picture}.jpg', host: 'localhost')]
    public function specific(
        Request $request,
        LoggerInterface $logger,
        string $picture,
    ): BinaryFileResponse {
        $this->setupFilesystem();
        return new BinaryFileResponse("{$this->getFramePath()}{$picture}.jpg");
    }

    #[Route('/frame.jpg', host: 'frame.hatt.style')]
    #[Route('/frame.hatt.style/frame.jpg', host: 'localhost')]
    public function random(
        Request $request,
        LoggerInterface $logger,
    ): Response {
        $pictures = $this->getPictures();
        $index = random_int(0, count($pictures) - 1);
        $picture = $pictures[$index];
        $img = new \Imagick("{$this->getFramePath()}{$picture}");
        $img->setImageCompressionQuality(100);
        $img->modulateImage(100.0, 150.0, 100.0);
        $blob = $img->getImageBlob();
        $img->destroy();
        return new Response($blob, 200, ['Content-Type' => 'image/jpeg']);
    }

    /** @return array<string> */
    protected function getPictures(): array {
        $this->setupFilesystem();
        $entries = scandir($this->getFramePath());
        $pictures = [];
        foreach ($entries as $entry) {
            if (substr($entry, 0, 1) !== '.') {
                $pictures[] = $entry;
            }
        }
        return $pictures;
    }

    protected function setupFilesystem(): void {
        if (!is_dir($this->getFramePath())) {
            mkdir($this->getFramePath());
        }
    }

    protected function getFramePath(): string {
        $private_path = $_ENV['PRIVATE_PATH'];
        return __DIR__."/../../../{$private_path}frame/";
    }

    protected function getHref(Request $request): string {
        $href = $request->getPathInfo();
        return substr($href, strlen($href) - 1, 1) === '/'
            ? substr($href, 0, strlen($href) - 1) : $href;
    }
}
