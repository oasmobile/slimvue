<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 19/09/2017
 * Time: 3:09 PM
 */

use Oasis\SlimVue\TwigBridgeInfo;
use Silex\Application;
use Silex\Provider\TwigServiceProvider;

require 'vendor/autoload.php';

$silex = new Application();
$silex->register(
    new TwigServiceProvider(),
    [
        'twig.path' => __DIR__ . "/templates",
    ]
);
$silex->error(
    function ($e) {
        var_dump($e);
    }
);
$silex->get(
    '/',
    function (Application $kernel) {
        /** @var \Twig_Environment $twig */
        $twig = $kernel['twig'];
        
        return $twig->render(
            'zxc.twig',
            [
                'name'   => 'Zhang Xu Chang',
                'bridge' => new TwigBridgeInfo(
                    [
                        'user' => 'yangyi',
                    ]
                ),
            ]
        );
    }
);
$silex->run();
