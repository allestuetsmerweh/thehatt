<?php

use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use PhpDeploy\AbstractDefaultDeploy;

require_once __DIR__.'/vendor/autoload.php';

class Deploy extends AbstractDefaultDeploy {
    protected function populateFolder(): void {
        $fs = new Symfony\Component\Filesystem\Filesystem();

        $build_folder_path = $this->getLocalBuildFolderPath();
        $fs->mirror(__DIR__, $build_folder_path);
    }

    protected function getFlysystemFilesystem(): Filesystem {
        $options = FtpConnectionOptions::fromArray([
            'host' => 's007.cyon.net', // required
            'root' => '/', // required
            'username' => $this->username, // required
            'password' => $this->password, // required
            'port' => 21,
            'ssl' => true,
            'timeout' => 90,
        ]);
        $adapter = new FtpAdapter($options);
        return new Filesystem($adapter);
    }

    public function getRemotePublicPath(): string {
        return 'public_html/deploy.hatt.style';
    }

    public function getRemotePublicUrl(): string {
        return "https://deploy.hatt.style";
    }

    public function getRemotePrivatePath(): string {
        return 'private_files';
    }

    /** @return array<string, string> */
    public function install(string $public_path): array {
        $getPublicPathForSubdomain = function (string $subdomain) use ($public_path): string {
            return str_replace($this->getRemotePublicPath(), "public_html/{$subdomain}", $public_path);
        };

        // hatt.style
        $base_public = $getPublicPathForSubdomain('hatt.style');
        $this->installForSubdomain($base_public);

        // flatastic.hatt.style
        $flatastic_public = $getPublicPathForSubdomain('flatastic.hatt.style');
        $this->installForSubdomain($flatastic_public);

        return [];
    }

    protected function installForSubdomain(string $public_path): void {
        $fs = new Symfony\Component\Filesystem\Filesystem();
        $fs->copy(__DIR__.'/public/.htaccess', "{$public_path}/.htaccess", true);
        $index_path = "{$public_path}/index.php";
        $index_contents = file_get_contents(__DIR__.'/public/index.php');
        $updated_index_contents = str_replace(
            "require_once dirname(__DIR__).'/vendor/autoload_runtime.php';",
            "require_once __DIR__.'/../../private_files/deploy/live/vendor/autoload_runtime.php';",
            $index_contents,
        );
        unlink($index_path);
        file_put_contents($index_path, $updated_index_contents);
    }
}

if (isset($_SERVER['argv'])) {
    $deploy = new Deploy();
    $logger = new Logger('deploy');
    $logger->pushHandler(new ErrorLogHandler());
    $deploy->setLogger($logger);
    $deploy->cli();
}