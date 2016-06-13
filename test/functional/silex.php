<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/mw.php';

$app = new Silex\Application();
$app->register(new Krak\Mw\Provider\Silex\MwServiceProvider(), [
    'krak.mw.middleware' => [onA404()],
]);

$app->get('/a', function() {
    return 'a';
});
$app->get('/b', function() {
    return 'b';
});

$app->run();
