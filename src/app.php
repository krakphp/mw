<?php

namespace Krak\Mw;

use Psr\Log\LoggerInterface,
    Zend\Diactoros;

/** An HttpApp is responsible for taking a kernel and generating the request,
    running the kernel, and then emitting the response */
interface HttpApp {
    /** @param callable|HttpKernel */
    public function __invoke($kernel);
}

/** Run the app using Diactoros PSR7 system */
function diactorosApp(
    Diactoros\Response\EmitterInterface $emitter = null,
    $req_factory = null
) {
    $emitter = $emitter ?: new Diactoros\Response\SapiEmitter();
    $req_factory = $req_factory ?: function() {
        return Diactoros\ServerRequestFactory::fromGlobals();
    };

    return function($kernel) use ($emitter, $req_factory) {
        $resp = $kernel($req_factory());
        $emitter->emit($resp);
    };
}

/** this is useful if you only define mw on certain routes, but there are routes
    that you want to go through to a different application */
function silentFailApp($app) {
    return function($kernel) use ($app) {
        try {
            $app($kernel);
        } catch (\Exception $e) {
            // silently fail
        }
    };
}
