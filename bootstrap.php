<?php

require_once __DIR__ . '/vendor/autoload.php';


use Silex\Application;
use Knp\Provider\ConsoleServiceProvider;

$app = new Application();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'    => __DIR__.'/views',
));

$app->register(new ConsoleServiceProvider(), array(
    'console.name'              => 'AZ Legislative Maps',
    'console.version'           => '0.0.1',
    'console.project_directory' => __DIR__
));


return $app;
