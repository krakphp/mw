<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Krak\Mw,
    Krak\Mw\Filter;

function addAttribute($name, $value) {
    return function($req, $next) use ($name, $value) {
        return $next($req->withAttribute($name, $value));
    };
}

function show404($resp_factory) {
    return function($req, $next) use ($resp_factory) {
        return $resp_factory(404, ['Content-Type' => 'text/plain'], 'not found');
    };
}

function resolveResponse($resp_factory) {
    return function($req, $next) use ($resp_factory) {
        $path = $req->getUri()->getPath();
        if ($path == '/a') {
            return $resp_factory(200, ['Content-Type' => 'text/plain'], 'A Response....');
        }
        else if ($path == '/b') {
            return $resp_factory(200, ['Content-Type' => 'text/html'], '<h1>B!</h1>');
        }

        return $resp_factory(200, ['Content-Type' => 'text/plain'], print_r($req->getAttributes(), true));
    };
}

$resp_factory = Mw\diactorosResponseFactory();

$kernel = Mw\mwHttpKernel([
    addAttribute('x-attr', 'some-value...'),
    Mw\filter(show404($resp_factory), Filter\path('~^(/d)$~')),
    resolveResponse($resp_factory)
]);

$app = Mw\diactorosApp();
$app($kernel);
