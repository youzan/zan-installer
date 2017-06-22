# Youzan Zan PHP Installer

## Installation
The recommended way to install zan-installer is through
[Composer](http://getcomposer.org).

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```
See [Composer Getting Started](https://getcomposer.org/doc/00-intro.md)


```bash
git clone https://github.com/youzan/zan-installer.git
```
The composer repository on packagist.org: [youzan/zan-installer](https://packagist.org/packages/youzan/zan-installer) 。

## Update
Use the following command to update zan-installer:

```bash
cd zan-installer
composer install
```


## Use
Now, you can open your favorite Terminal tool, type `zan` to use!

```bash
php zan↵
   __    __
  /\ \  /\ \
  \ `\`\\/'/ ___   __  __  ____      __      ___
   `\ `\ /' / __`\/\ \/\ \/\_ ,`\  /'__`\  /' _ `\
     `\ \ \/\ \L\ \ \ \_\ \/_/  /_/\ \L\.\_/\ \/\ \
       \ \_\ \____/\ \____/ /\____\ \__/.\_\ \_\ \_\
        \/_/\/___/  \/___/  \/____/\/__/\/_/\/_/\/_/
Create a new ZanPhp application.
Which type application would you create? (use <space> to select)
❯ ● HTTP
  ○ TCP
Your application name: (ex: zanphp-demo) demo
Please input a output directory:
/data/www
Your composer name: (ex: zanphp/zanhttp) youzan/demo
Your application namespace: (ex: Com\Youzan\ZanHttpDemo\) Com\Youzan\Demo\
Downloading the source code archive ...
Extracting archive ...
Congratulations, your application has been generated to the following directory.
/data/www/demo/
See /data/www/demo/README.md for information on how to run.
Composer installing...
Loading composer repositories with package information
Updating dependencies (including require-dev)
  - Installing zanphp/zan (dev-master ebf9014)
    Cloning ebf901442054c358da4c2e9188cd8b9f4acc7e10

Writing lock file
Generating autoload files
```

## Also see
- [Youzan Zan PHP framework &#9992;](https://github.com/youzan/zan) 
- [Zan PHP Framework official site(zanphp.io) source code &#9992;](https://github.com/youzan/zanphp.io-server)
- [ZanPhp HTTP demo for zan framework &#9992;](https://github.com/youzan/zanhttp)

## License
The zan-installer is open-sourced software licensed under the Apache-2.0 license.

