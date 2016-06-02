<?php

/**
 * Created by IntelliJ IDEA.
 * User: nuomi
 * Date: 16/6/1
 * Time: 下午5:25
 */

namespace Zan\Installer\Console;

use League\CLImate\CLImate;
use RuntimeException;
use ZipArchive;

class Installer
{
    private $appName = 'zanphp-demo';
    private $config = [
        'http' => [
            'name' => 'zanhttp-latest',
            'url' => 'https://github.com/youzan/zanhttp/archive/latest.zip',
        ],
        'tcp' => [
            'name' => 'zantcp-latest',
            'url' => 'https://github.com/youzan/zantcp/archive/latest.zip',
        ]
    ];
    private $climate;
    private $type;
    private $directory;

    public function init()
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        $this->climate = new CLImate();
        $this->showWelcome();

        $this->type = $this->getAppTypeFromPrompt();

        $this->appName = $this->getAppNameFromInput();

        $this->directory = $this->getDirectoryFromInput();

        $this->install();
    }

    private function getConfig($type)
    {
        if (!isset($this->config[$type])) {
            throw new RuntimeException('Config type error: ', $type);
        }
        return $this->config[$type];
    }

    private function showWelcome()
    {
        $this->climate->lightRed('Create a new ZanPhp application.');
    }

    private function getAppTypeFromPrompt()
    {
        $type = $this->showAppTypePrompt();
        if (!$type) {
            $this->climate->red('Please use <space> to select the application type!');
            $type = $this->showAppTypePrompt();
        }
        return $type;
    }

    private function showAppTypePrompt()
    {
        $options = [
            'http' => 'HTTP',
            'tcp' => 'TCP',
        ];
        $input = $this->climate->lightGreen()->radio('Which type application would you create?', $options);
        $response = $input->prompt();
        return $response;
    }

    private function getAppNameFromInput()
    {
        $input = $this->climate->lightGreen()->input("Please input your application name:");
        $input->defaultTo($this->appName);
        $response = trim($input->prompt());
        if ($response === $this->appName) {
            $this->climate->red('Use default application name: ' . $this->appName);
        }
        return $response;
    }

    private function getDirectoryFromInput()
    {
        $directory = $this->showDirectoryInput();
//        if ($directory && !is_dir($directory)) {
//            $this->climate->red('[' . $directory . '] is not a valid directory!');
//            $directory = $this->showDirectoryInput();
//        }
        if ($this->startsWith($directory, '~')) {
            $directory = str_replace('~', $this->getUserHomePath(), $directory);
        }
        if (!$this->startsWith($directory, '/')) {
            $directory = getcwd() . '/output/' . $directory;
        }
        if (!$this->endsWith($directory, '/')) {
            $directory .= '/';
        }
        $result = $directory . $this->appName . '/';
        return $result;
    }

    private function showDirectoryInput()
    {
        $input = $this->climate->lightGreen()->input("Please input a output directory:");
        $default = getcwd() . '/output/';
        $input->defaultTo($default);
        $response = trim($input->prompt());
        if ($response === $default) {
            $this->climate->red($default);
        }
        return $response;
    }

    private function startsWith($haystack, $needle)
    {
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    private function endsWith($haystack, $needle)
    {
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    /**
     * Return the user's home directory.
     * This function is taken from the Drush project.
     * @see https://github.com/drush-ops/drush
     * @return null|string
     */
    private function getUserHomePath()
    {
        // Cannot use $_SERVER superglobal since that's empty during UnitUnishTestCase
        // getenv('HOME') isn't set on Windows and generates a Notice.
        $home = getenv('HOME');
        if (!empty($home)) {
            // home should never end with a trailing slash.
            $home = rtrim($home, '/');
        } elseif (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
            // home on windows
            $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
            // If HOMEPATH is a root directory the path can end with a slash. Make sure
            // that doesn't happen.
            $home = rtrim($home, '\\/');
        }
        return empty($home) ? NULL : $home;
    }

    private function install()
    {
        $zipFile = $this->getRandomFileName();
        $this->download($zipFile)
            ->extract($zipFile)
            ->cleanUp($zipFile);
        $this->climate->lightRed('Congratulations, your application has been generated to the following directory.');
        $this->climate->lightGreen($this->directory);
    }

    private function getRandomFileName()
    {
        return getcwd() . '/tmp/zan_' . md5(time() . uniqid()) . '.zip';
    }

    private function download($zipFile)
    {
        $url = $this->getConfig($this->type)['url'];
        if (!$url) {
            throw new RuntimeException('Error download url', $url);
        }

        $this->climate->lightGreen('Downloading the source code archive ...');
        $res = file_get_contents($url);
        if (false === $res) {
            $this->climate->red('Download fail :(');
            exit();
        }
        file_put_contents($zipFile, $res);

        return $this;
    }

    private function extract($zipFile)
    {
        $this->climate->lightGreen('Extracting archive ...');
        $archive = new ZipArchive;

        $tmpDirectory = $this->getDirectory(getcwd() . '/tmp/');

        if (true === $archive->open($zipFile)) {
            $archive->extractTo($tmpDirectory);
            $archive->close();
        }

        $tmpDirectory .= $this->getConfig($this->type)['name'];
        $targetDirectory = $this->getDirectory($this->directory);
        rename($tmpDirectory, $targetDirectory);
        return $this;
    }

    private function getDirectory($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            chmod($dir, 0755);
        }
        return $dir;
    }

    private function cleanUp($zipFile)
    {
        @chmod($zipFile, 0777);

        @unlink($zipFile);

        return $this;
    }

}
