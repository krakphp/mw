# Mw (Middleware)

The Mw library is a very flexible framework for handling requests.

## Installation

You can install this as composer package at `krak/mw`

## Usage

Here's an example of basic usage of the mw library.

```php
<?php

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
    Mw\filter(show404($resp_factory), function($req) { return $req->getUri()->getPath() == '/d'; }),
    resolveResponse($resp_factory)
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

This is just the core library for the middleware framework. There are a ton more components for things like http auth, routing, exception handling, REST Framework integration, Web Framework Integration, Symfony/Silex integration and more!
