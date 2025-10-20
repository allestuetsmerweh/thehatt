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
        if ($this->target === 'cyon') {
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
        throw new Exception("Target must be `cyon`");
    }

    public function getRemotePublicPath(): string {
        if ($this->target === 'cyon') {
            if ($this->environment === 'staging') {
                return 'public_html/staging.thehatt.ch';
            }
            if ($this->environment === 'prod') {
                return 'public_html/deploy.thehatt.ch';
            }
            throw new Exception("Environment must be `staging` or `prod`");
        }
        throw new Exception("Target must be `cyon`");
    }

    public function getRemotePublicUrl(): string {
        if ($this->target === 'cyon') {
            if ($this->environment === 'staging') {
                return "https://staging.thehatt.ch";
            }
            if ($this->environment === 'prod') {
                return "https://deploy.thehatt.ch";
            }
            throw new Exception("Environment must be `staging` or `prod`");
        }
        throw new Exception("Target must be `cyon`");
    }

    public function getRemotePrivatePath(): string {
        if ($this->target === 'cyon') {
            if ($this->environment === 'staging') {
                return 'private_files/staging';
            }
            if ($this->environment === 'prod') {
                return 'private_files/prod';
            }
            throw new Exception("Environment must be `staging` or `prod`");
        }
        throw new Exception("Target must be `cyon`");
    }

    /** @return array<string, string> */
    public function install(string $public_path): array {
        $this->logger->info("Prepare for installation (env={$this->environment})...");

        $fs = new Symfony\Component\Filesystem\Filesystem();
        $fs->copy(__DIR__.'/../../.env.local', __DIR__.'/.env.local');

        $public_url = $this->getRemotePublicUrl();
        $staging_token = null;
        $install_path = $public_path;
        if ($this->environment === 'staging') {
            $entries = scandir($public_path);
            foreach ($entries as $entry) {
                $path = "{$public_path}/{$entry}";
                if ($entry[0] !== '.' && is_dir($path) && $fs->exists("{$path}/_TOKEN_DIR_WILL_BE_REMOVED.txt")) {
                    $fs->remove($path);
                }
            }
            $staging_token = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(openssl_random_pseudo_bytes(18)));
            $this->logger->info("--------------------------------------------");
            $this->logger->info("   {$public_url}/{$staging_token}/   ");
            $this->logger->info("--------------------------------------------");
            $install_path = "{$public_path}/{$staging_token}";
        }

        $getPublicPathForSubdomain = function (string $subdomain) use ($install_path): string {
            return str_replace($this->getRemotePublicPath(), "public_html/{$subdomain}", $install_path);
        };

        if ($this->environment === 'staging') {
            // staging.thehatt.ch
            $base_public = $getPublicPathForSubdomain('staging.thehatt.ch');
            $this->installForSubdomain($base_public);
            file_put_contents("{$install_path}/_TOKEN_DIR_WILL_BE_REMOVED.txt", '');
        }
        if ($this->environment === 'prod') {
            // thehatt.ch
            $base_public = $getPublicPathForSubdomain('thehatt.ch');
            $this->installForSubdomain($base_public);
        }

        return [
            'staging_token' => $staging_token,
        ];
    }

    protected function installForSubdomain(string $install_path): void {
        $levels = count(explode('/', $install_path)) - count(explode('/', __DIR__)) + 4;
        $fs = new Symfony\Component\Filesystem\Filesystem();
        $fs->copy(__DIR__.'/public/.htaccess', "{$install_path}/.htaccess", true);
        $index_path = "{$install_path}/index.php";
        $index_contents = file_get_contents(__DIR__.'/public/index.php');
        $updated_index_contents = str_replace(
            "require_once dirname(__DIR__).'/vendor/autoload_runtime.php';",
            "require_once dirname(__DIR__, {$levels}).'/private_files/{$this->environment}/deploy/live/vendor/autoload_runtime.php';",
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
