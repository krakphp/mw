<?php

// run with: php -S 127.0.0.1:8000 example/readme.php

require_once __DIR__ . '/../vendor/autoload.php';

use Krak\Mw,
    Krak\HttpMessage\Match;

function injectAttribute($name, $value) {
    return function($req, $next) use ($name, $value) {
        return $next($req->withAttribute($name, $value));
    };
}

function show404($resp_factory) {
    return function($req, $next) use ($resp_factory) {
        return $resp_factory(404, ['Content-Type' => 'text/plain'], 'not found');
    };
}

$rf = Mw\diactorosResponseFactory();
$json_rf = Mw\jsonResponseFactory($rf, JSON_PRETTY_PRINT);
$html_rf = Mw\htmlResponseFactory($rf);
$text_rf = Mw\textResponseFactory($rf);

$kernel = Mw\mwHttpKernel([
    injectAttribute('x-attr', 'value'),
    Mw\filter(show404($rf), function($req) { return $req->getUri()->getPath() == '/d'; }),
    Mw\on('/a', function() use ($text_rf) {
        return $text_rf(200, [], 'A Text Response');
    }),
    Mw\on('GET', '/b', function() use ($html_rf) {
        return $html_rf(200, [], '<h1>An Html Response</h1>');
    }),
    function($req) use ($json_rf) {
        return $json_rf(200, [], $req->getAttributes());
    }
]);

$app = Mw\diactorosApp();
$app($kernel);
