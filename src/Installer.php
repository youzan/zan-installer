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
    private $namespace = 'zanphp/zanhttp';
    private $config = [
        'http' => [
            'name' => 'zanhttp-latest',
            'url' => 'https://github.com/youzan/zanhttp-boilerplate/archive/latest.zip',
        ],
        'tcp' => [
            'name' => 'zantcp-latest',
            'url' => 'https://github.com/youzan/zantcp-boilerplate/archive/latest.zip',
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

        $this->namespace = $this->getNamespaceFromInput();

        $this->directory = $this->getDirectoryFromInput();

        $this->checkApplicationIsExist();

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
        $this->climate->lightRed("   __    __                                          ");
        $this->climate->lightRed("  /\\ \\  /\\ \\                                         ");
        $this->climate->lightRed("  \\ `\\`\\\\/'/ ___   __  __  ____      __      ___     ");
        $this->climate->lightRed("   `\\ `\\ /' / __`\\/\\ \\/\\ \\/\\_ ,`\\  /'__`\\  /' _ `\\   ");
        $this->climate->lightRed("     `\\ \\ \\/\\ \\L\\ \\ \\ \\_\\ \\/_/  /_/\\ \\L\\.\\_/\\ \\/\\ \\  ");
        $this->climate->lightRed("       \\ \\_\\ \\____/\\ \\____/ /\\____\\ \\__/.\\_\\ \\_\\ \\_\\ ");
        $this->climate->lightRed("        \\/_/\\/___/  \\/___/  \\/____/\\/__/\\/_/\\/_/\\/_/ ");

        $this->climate->lightGreen('Create a new ZanPhp application.');
    }

    private function getAppTypeFromPrompt()
    {
        $type = $this->showAppTypePrompt();
        if (!$type) {
            $this->climate->blue('Please use <space> to select the application type!');
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
        $msg = 'Your application name: (ex: ' . $this->appName . ')';
        $input = $this->climate->lightGreen()->input($msg);
        $input->defaultTo($this->appName);
        $response = trim($input->prompt());
        if ($response === $this->appName) {
            $this->climate->blue('Use default application name: ' . $this->appName);
        }
        var_dump($response);
        return $response;
    }

    private function getNamespaceFromInput()
    {
        $msg = 'Your application namespace: (ex: ' . $this->namespace . ')';
        $input = $this->climate->lightGreen()->input($msg);
        $input->defaultTo($this->appName);
        $response = trim($input->prompt());
        if ($response === $this->appName) {
            $this->climate->blue('Use default namespace: ' . $this->namespace);
        }
        return $response;
    }

    private function getDirectoryFromInput()
    {
        $directory = $this->showDirectoryInput();
//        if ($directory && !is_dir($directory)) {
//            $this->climate->blue('[' . $directory . '] is not a valid directory!');
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
            $this->climate->blue($default);
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
            ->cleanUp($zipFile)
            ->setAppName()
            ->setNamespace();

        $this->climate->lightRed('Congratulations, your application has been generated to the following directory.');
        $this->climate->lightGreen($this->directory);
        $this->climate->lightRed('See ' . $this->directory . 'README.md for information on how to run.!');
    }

    private function getRandomFileName()
    {
        return getcwd() . '/tmp/zan_' . md5(time() . uniqid()) . '.zip';
    }

    private function download($zipFile)
    {
        $url = $this->getConfig($this->type)['url'];
        if (!$url) {
            $this->climate->lightRed('ERROR: Download url error!');
            exit();
        }

        $this->climate->lightGreen('Downloading the source code archive ...');

        $res = file_get_contents($url);
        if (false === $res) {
            $this->climate->lightRed('ERROR: Download code fail :(');
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

    private function updateFileContent($targetFile, $key, $value)
    {
        $code = file_get_contents($targetFile);
        if (false === $code) {
            $this->climate->blue('Set %s fail :(', $key);
            exit();
        }

        $code = str_replace($key, $value, $code);
        if (false === file_put_contents($targetFile, $code)) {
            $this->climate->blue('Set %s fail :(', $key);
            exit();
        }
    }

    private function setAppName()
    {
        $targetFile = $this->directory . '/init/app.php';
        $this->updateFileContent($targetFile, '{{APP_NAME}}', $this->appName);

        return $this;
    }

    private function setNamespace()
    {
        $targetFile = $this->directory . 'composer.json';
        $this->updateFileContent($targetFile, '{{NAMESPACE}}', $this->namespace);

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

    private function checkApplicationIsExist()
    {
        if (is_dir($this->directory)) {
            $this->climate->lightRed('ERROR: Application already exists!');
            $this->climate->lightRed($this->directory);
            exit();
        }
    }

}
