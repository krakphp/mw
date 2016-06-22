<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Zend\Diactoros;

function onA404() {
    return Krak\Mw\filter(function($req, $next) {
        return new Diactoros\Response\TextResponse('Not Found', 404);
    }, Krak\Mw\Filter\path('~^/a.*~'));
}

function onCAttr() {
    return Krak\Mw\filter(function($req, $next) {
        return $next($req->withAttribute('x-attr', 'value'));
    }, Krak\Mw\Filter\path('/c', false));
}

function printReqAttributes() {
    return function($req, $next) {
        $resp = $next($req);
        if ($resp->getStatusCode() == 200) {
            $body = $resp->getBody();
            $body->write("<pre>" . print_r($req->getAttributes(), true) . "</pre>");
        }

        return $resp;
    };
}

function throwEx() {
    return function() {
        throw new Exception('yo! this is an exception message!');
    };
}

function exceptionHandler($req, $e) {
    return new Diactoros\Response\TextResponse('An exception occurred - ' . $e->getMessage(), 500);
}

$app = new Silex\Application();

$app->get('/a', function() {
    return 'a';
});
$app->get('/b', function() {
    return 'b';
});
$app->get('/c', function(Symfony\Component\HttpFoundation\Request $req) {
    return 'c';
});
$app->finish(function($req, $resp) {
    exit('1212');
});

list($mw, $mwapp) = Krak\Mw\Symfony\symfonyFactory($app);

$kernel = Krak\Mw\mwHttpKernel([
    Krak\Mw\catchException('exceptionHandler'),
    onA404(),
    onCAttr(),
    Krak\Mw\filter(throwEx(), Krak\Mw\Filter\path('/b', false)),
    printReqAttributes(),
    $mw,
]);

$mwapp($kernel);
