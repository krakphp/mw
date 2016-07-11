<?php

// run with: php -S 127.0.0.1:8000 example/diactoros-app.php

require_once __DIR__ . '/../vendor/autoload.php';

use Krak\Mw,
    Krak\HttpMessage\Match;

/** Adds an attribute to the request */
function addAttribute($name, $value) {
    return function($req, $next) use ($name, $value) {
        return $next($req->withAttribute($name, $value));
    };
}

/** Takes an attribute and appends it's value */
function appendAttribute($name, $value) {
    return function($req, $next) use ($name, $value) {
        return $next($req->withAttribute($name, $req->getAttribute($name) . $value));
    };
}

/** Always generates a 404 response */
function show404($resp_factory) {
    return function($req, $next) use ($resp_factory) {
        return $resp_factory(404, ['Content-Type' => 'text/plain'], 'not found');
    };
}

/** this will compare the uri and return a response */
function resolveResponse($resp_factory) {
    return function($req, $next) use ($resp_factory) {
        $path = $req->getUri()->getPath();
        if ($path == '/a') {
            return $resp_factory(200, ['Content-Type' => 'text/plain'], 'A Response....');
        }
        else if ($path == '/b') {
            return $resp_factory(200, ['Content-Type' => 'text/html'], '<h2 class="text-success">B!</h2>');
        }

        return $resp_factory(200, ['Content-Type' => 'text/plain'], print_r($req->getAttributes(), true));
    };
}

/** this wraps all html responses with bootstrap and proper html scaffolding */
function wrapHtml() {
    return function($req, $next) {
        $start = <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css"/>
    </head>
    <body>
        <div class="container">
HTML;

        $end = <<<HTML
        </div>
    </body>
</html>
HTML;
        $resp = $next($req);

        if ($resp->getHeader('Content-Type')[0] != 'text/html') {
            return $resp;
        }

        $middle = $resp->getBody();
        return $resp->withBody(new GuzzleHttp\Psr7\AppendStream([
            GuzzleHttp\Psr7\stream_for($start),
            $middle,
            GuzzleHttp\Psr7\stream_for($end),
        ]));
    };
}

$resp_factory = Mw\guzzleResponseFactory();
$ex_handler = function($req, $e) use ($resp_factory) {
    return $resp_factory(500, ['Content-Type' => 'text/plain'], (string) $e);
};

$kernel = Mw\mwHttpKernel([
    Mw\catchException($ex_handler),
    addAttribute('x-attr', 'value: '),
    appendAttribute('x-attr', 'a'),
    Mw\compose([
        appendAttribute('x-attr', 'c'),
        appendAttribute('x-attr', 'b')
    ], Mw\ORDER_LIFO),
    Mw\filter(appendAttribute('x-attr', 'd'), Match\path('/d', Match\CMP_EQ)),
    Mw\filter(function() { throw new \Exception('bad something...'); }, Match\path('/e', Match\CMP_EQ)),
    Mw\filter(show404($resp_factory), Match\path('~^/f$~')),
    wrapHtml(),
    resolveResponse($resp_factory),
]);

$app = Mw\diactorosApp();
$app($kernel);
