# Mw (Middleware)

The Mw library is a very flexible framework for handling requests.

## Installation

You can install this as composer package at `krak/mw`

## Usage

Here's an example of basic usage of the mw library.

```php
<?php

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
```

For a more full featured example, look in the `example` directory.

### HttpKernel

The kernels are responsible for taking a `ServerRequestInterface` and returning a `ResponseInterface`.

### Middleware

A middleware is also responsible for returning a request into a response; however, they are designed to easily be chainable instead of having to use decoration to add functionality. Currently, middleware is only useful when using the `mwHttpKernel` which transforms a set of middleware into a kernel.

### Response Factory

A response factory creates a response PSR7 response using any psr7 library you wish. Currently, we only support guzzle and diactoros, but you create your own simply or use the `defaultResponseFactory`, to always return a defualt resonse.

The benefit of using the response factory comes from 3rd party middleware so that they don't have to be dependent on a psr7 library.

### HttpApp

The app component simply accepts a kernel and runs everything. The typical job of the http app is to generate a request, feed it to the kernel, and then emit the response.

## Components

This is just the core library for the middleware framework. There are a ton more components for things like http auth, routing, exception handling, REST Framework integration, Web Framework Integration, Symfony/Silex integration, CodeIgniter integration, and more!

- [Routing](https://gitlab.bighead.net/krak-mw/mw-routing)
- [JWT Authentication](https://gitlab.bighead.net/krak-mw/mw-jwt-auth)
- [CodeIgniter Integration](https://gitlab.bighead.net/krak-mw/mw-codeigniter)
