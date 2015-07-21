<?php

require __DIR__.'/../vendor/autoload.php';


$app = new Silex\Application();

//$app->register(new DerAlex\Silex\YamlConfigServiceProvider(__DIR__ . '/../config.build.yaml'));
$app->register(new DerAlex\Silex\YamlConfigServiceProvider(__DIR__ . '/../config.yaml'));

$app['debug'] = true;//$app['config']['debug'];

$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' => __DIR__.'/../views',
]);

$app->register(new Silex\Provider\DoctrineServiceProvider(), [
    'db.options' => $app['config']['database'],
]);

$app->mount('/login', new PrintApp\Controllers\LoginControllerProvider());
$app->mount('/logout', new PrintApp\Controllers\LogoutControllerProvider());
$app->mount('/print', new PrintApp\Controllers\PrintControllerProvider());

$app->run();
