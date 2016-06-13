# Mw (Middleware)

The Mw library is a simple framework agnostic way to create middleware for several different frameworks.

## Installation

You can install this as composer package at `krak/mw`

## Usage

This library is fairly small and only exports two interfaces: Middleware and MwResolve and excluding the providers, this library is under 100 lines of code all in the [src/mw.php](src/mw.php).

A middleware resolver will take a request and return a modified request/response tuple.

A middleware simply takes a request and a next func and applies any transformations and either returns a response or defers to the next function.

For demonstration, we will assume each of the above examples has this middleware available

```php
<?php

/** shows a 404 when the request uri matches */
function onUri404($uri) {
    return function($req, $next) use ($uri){
        if ($req->getRequestUri() == $uri) {
            return new Symfony\Component\HttpFoundation\Response('not found', 404);
        }

        return $next($req);
    };
}
```

### Silex

```php
<?php

$app = new Silex\Application();
$app->register(new Krak\Mw\Provider\Silex\MwServiceProvider(), [
    'krak.mw.middleware' => [onUri404('/a')],
]);

$app->get('/a', function() {
    return 'a';
});
$app->get('/b', function() {
    return 'b';
});

$app->run();
```

This will show a 404 response on the `/a` route.

### Stackphp

This example shows stackphp using silex as the primary kernel.

```php
<?php

$app = new Silex\Application();
$kernel = new Krak\Mw\Provider\Stack\StackMwKernel($app, Krak\Mw\mw_resolve([
    onUri404('/a')
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
```

### Creating your own Resolver

There is currently only one resolver the `mw_resolve` function. For convenience, you can use the `MwStackResolve` which is a mutable wrapper around the `mw_resolve` and allows you to modify the stack of middlewares before resolving.

```php
<?php

use Krak\Mw\MwStackResolve;
use function Krak\Mw\mw_resolve;
use const Krak\Mw\ORDER_LIFO,
    Krak\Mw\ORDER_FIFO;

$resolve = mw_resolve([
    middleware1(),
    middleware2(),
], ORDER_FIFO); // First middleware defined is the first middleware executed. LIFO is opposite

list($req, $resp) = $resolve($req);

// or if, you prefer

$resolve = new MwStackResolve([
    middleware1()
]);
$resolve->pushMw(middleware2());
list($req, $resp) = $resolve($req);
// middleware 2 executes first because it uses LIFO (like a stack)
```

## Providers

Currently, the mw library has Silex and Stackphp providers.
