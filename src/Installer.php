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
    private $composerName = 'zanphp/zanhttp';
    private $namespace = 'Com\\Youzan\\ZanHttpDemo\\';
    private $config = [
        'http' => [
            'name' => 'zanhttp-boilerplate-master',
            'url' => 'https://codeload.github.com/youzan/zanhttp-boilerplate/zip/master',
            'execute' => 'httpd'
        ],
        'tcp' => [
            'name' => 'zantcp-boilerplate-master',
            'url' => 'https://codeload.github.com/youzan/zantcp-boilerplate/zip/master',
            'execute' => 'nova'
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

        $this->wizard();
    }

    private function wizard()
    {
        $this->type = $this->getAppTypeFromPrompt();

        $this->appName = $this->getAppNameFromInput();

        $this->directory = $this->getDirectoryFromInput();

        $this->checkApplicationIsExist();

        $this->composerName = $this->getComposerNameFromInput();

        $this->namespace = $this->getNamespaceFromInput();

        $this->install();

        $this->composer();
    }

    private function getConfig($type)
    {
        if (NULL == $type) {
            $type = 'http';
        }
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
            $this->climate->lightRed('Please use <space> to select the application type!');
            $type = $this->getAppTypeFromPrompt();
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
        return $response;
    }

    private function getComposerNameFromInput()
    {
        $msg = 'Your composer name: (ex: ' . $this->composerName . ')';
        $input = $this->climate->lightGreen()->input($msg);
        $input->defaultTo($this->composerName);
        $response = trim($input->prompt());
        if ($response === $this->composerName) {
            $this->climate->blue('Use composer name: ' . $this->composerName);
        }
        return $response;
    }

    private function getNamespaceFromInput()
    {
        $msg = 'Your application namespace: (ex: ' . $this->namespace . ')';
        $input = $this->climate->lightGreen()->input($msg);
        $input->defaultTo($this->namespace);
        $response = trim($input->prompt());
        if ($response === $this->namespace) {
            $this->climate->blue('Use default namespace: ' . $this->namespace);
        }
        if (false == strpos($response, "\\")) {
            $this->climate->lightRed('ERROR: The namespace you provided is invalid, please re-enter.');
            $response = $this->getNamespaceFromInput();
        }
        $response = $this->fixNamespace($response);
        return $response;
    }

    private function fixNamespace($namespace)
    {
        $arr = explode("\\", $namespace);
        $arr = array_filter($arr);
        $namespace = join($arr, '\\');
        $namespace .= "\\";
        return $namespace;
    }

    private function convertToComposerNamespace($namespace)
    {
        $namespace = str_replace('\\', '\\\\', $namespace);
        return $namespace;
    }

    private function getDirectoryFromInput()
    {
        $directory = $this->showDirectoryInput();
        if ($this->startsWith($directory, '~')) {
            $directory = str_replace('~', $this->getUserHomePath(), $directory);
        }

        if (!$this->startsWith($directory, '/')) {
            $directory = getcwd() . $directory;
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
        $default = getcwd();
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
            ->setComposer()
            ->setSourceNamespace()
            ->setupTestcase()
            ->setExecute();

        $this->climate->lightRed('Congratulations, your application has been generated to the following directory.');
        $this->climate->lightGreen($this->directory);
        $this->climate->lightRed('See ' . $this->directory . 'README.md for information on how to run.');
    }

    private function getRandomFileName()
    {
        return getcwd() . '/zan_' . md5(time() . uniqid()) . '.zip';
    }

    private function download($zipFile)
    {
        $this->climate->lightGreen('Downloading the source code archive ...');

        $url = $this->getConfig($this->type)['url'];
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

        $tmpDirectory = getcwd();

        if (true === $archive->open($zipFile)) {
            $innerDirName = $this->getInnerDirName($archive);
            $archive->extractTo($tmpDirectory);
            $archive->close();
        }

        $tmpDirectory .= '/' . $innerDirName;
        $targetDirectory = $this->getDirectory($this->directory);
        rename($tmpDirectory, $targetDirectory);
        chmod($targetDirectory, 0755);

        return $this;
    }

    private function getInnerDirName($archive)
    {
        if ($archive->numFiles == 0) {
            throw new RuntimeException('ERROR: Get inner dir name fail.');
        }
        $stat = $archive->statIndex(0);
        $result = basename($stat['name']);
        return $result;
    }

    private function updateFileContent($targetFile, $key, $value)
    {
        $code = file_get_contents($targetFile);
        if (false === $code) {
            $this->climate->blue('Set ' . $key . ' fail :(');
            exit();
        }

        $code = str_replace($key, $value, $code);
        if (false === file_put_contents($targetFile, $code)) {
            $this->climate->blue('Set ' . $key . ' fail :(');
            exit();
        }
    }

    private function setAppName()
    {
        $targetFile = $this->directory . 'init/app.php';
        $this->updateFileContent($targetFile, '{{APP_NAME}}', $this->appName);

        return $this;
    }

    private function setComposer()
    {
        $targetFile = $this->directory . 'composer.json';

        $this->updateFileContent($targetFile, '{{NAME}}', $this->composerName);

        $composerNamespace = $this->convertToComposerNamespace($this->namespace);
        $this->updateFileContent($targetFile, '{{NAMESPACE}}', $composerNamespace);

        return $this;
    }

    private function setSourceNamespace()
    {
        $controller = $this->directory . 'src/Controller/Demo/IndexController.php';
        $this->updateFileContent($controller, '{{NAMESPACE}}', $this->namespace);

        $service = $this->directory . 'src/DemoModule/Service/DemoService.php';
        $this->updateFileContent($service, '{{NAMESPACE}}', $this->namespace);

        return $this;
    }

    private function setupTestcase()
    {
        $testName = $this->appName . 'Test';
        $bootstrap = $this->directory . 'tests/bootstrap.php';
        $this->updateFileContent($bootstrap, '{{APP_TEST_NAME}}', $testName);

        $test = $this->directory . 'tests/DemoModule/DemoTest.php';
        $this->updateFileContent($test, '{{NAMESPACE}}', $this->namespace);

        return $this;
    }

    private function setExecute()
    {
        $targetFile = $this->directory . 'bin/' . $this->getConfig($this->type)['execute'];
        chmod($targetFile, 0777);
        return $this;
    }

    private function getDirectory($dir)
    {
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

    private function composer()
    {
        $this->climate->lightGreen('Composer installing...');
        $cmd = 'cd ' . $this->directory . ' && composer install';
        $output = shell_exec($cmd);
        if (empty($output)) {
            return;
        }
        $this->climate->lightRed('ERROR: ' . $output);
    }

}
