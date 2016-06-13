<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/mw.php';

$app = new Silex\Application();
$kernel = new Krak\Mw\Provider\Stack\StackMwKernel($app, Krak\Mw\mw_resolve([
    onA404(),
]));

$app->get('/a', function() {
    return 'a';
});
$app->get('/b', function() {
    return 'b';
});

$req = Symfony\Component\HttpFoundation\Request::createFromGlobals();
$resp = $kernel->handle($req);
$resp->send();
