<?php

use PhpDeploy\AbstractDefaultDeploy;

require_once __DIR__.'/vendor/autoload.php';

class Deploy extends AbstractDefaultDeploy {
    protected function populateFolder() {
        $fs = new Symfony\Component\Filesystem\Filesystem();

        $build_folder_path = $this->getLocalBuildFolderPath();
        $fs->mirror(__DIR__, $build_folder_path);
    }

    protected function getFlysystemFilesystem() {
        $options = League\Flysystem\Ftp\FtpConnectionOptions::fromArray([
            'host' => 's007.cyon.net', // required
            'root' => '/', // required
            'username' => $this->username, // required
            'password' => $this->password, // required
            'port' => 21,
            'ssl' => true,
            'timeout' => 90,
        ]);
        $adapter = new League\Flysystem\Ftp\FtpAdapter($options);
        return new League\Flysystem\Filesystem($adapter);
    }

    public function getRemotePublicPath() {
        return 'public';
    }

    public function getRemotePublicUrl() {
        return "https://flatastic.hatt.style";
    }

    public function getRemotePrivatePath() {
        return 'private';
    }

    public function install($public_path) {
        $fs = new Symfony\Component\Filesystem\Filesystem();
        $fs->mirror(__DIR__.'/public', $public_path, null, ['delete' => true]);
        $index_path = "{$public_path}/index.php";
        $index_contents = file_get_contents($index_path);
        $updated_index_contents = str_replace(
            "require_once dirname(__DIR__).'/vendor/autoload_runtime.php';",
            "require_once dirname(__DIR__).'/private/deploy/live/vendor/autoload_runtime.php';",
            $index_contents,
        );
        unlink($index_path);
        file_put_contents($index_path, $updated_index_contents);
    }
}

if (isset($_SERVER['argv'])) {
    $deploy = new Deploy();
    $deploy->cli();
}