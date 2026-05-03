<?php

use Oasis\Mlib\Http\MicroKernel;

require 'vendor/autoload.php';

$config = [
    'routing' => [
        'path'       => 'routes.yml',
        'namespaces' => ['Oasis\\SlimVue\\Demo\\'],
    ],
    'twig' => [
        'template_dir' => __DIR__ . '/templates',
    ],
    'error_handlers' => [
        new \Oasis\SlimVue\Demo\DemoErrorHandler(),
    ],
];

$kernel = new MicroKernel($config, isDebug: true);
$kernel->run();
