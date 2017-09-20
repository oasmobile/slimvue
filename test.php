#! /usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 19/09/2017
 * Time: 3:09 PM
 */

use Oasis\SlimVue\SlimVueInitializeCommand;

require 'vendor/autoload.php';

$app = new Symfony\Component\Console\Application();
$app->addCommands([
    new SlimVueInitializeCommand('run')
]);
$app->run();
