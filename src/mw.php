<?php

namespace Krak\Mw;

require_once __DIR__ . '/Filter/filter.php';
require_once __DIR__ . '/Symfony/symfony.php';
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/kernel.php';
require_once __DIR__ . '/response_factory.php';

use Psr\Http\Message;

interface Middleware {
    /** @param Message\ServerRequestInterface $req
        @param \Closure $next a func that takes in a ServerRequestInterface object
        @return Message\ResponseInterface
    */
    public function __invoke(Message\ServerRequestInterface $req, $next);
}

function filter($mw, $predicate) {
    return function(Message\ServerRequestInterface $req, $next) use ($mw, $predicate) {
        if ($predicate($req)) {
            return $mw($req, $next);
        }

        return $next($req);
    };
}

/** lazily create the middleware once it needs to be executed */
function lazy($mw) {
    return function(Message\ServerRequestInterface $req, $next) use ($mw_gen) {
        static $mw;
        if (!$mw) {
            $mw = $mw_gen();
        }

        return $mw($req, $next);
    };
}

/** catches an execption delegates exception to a handler */
function catchException($handler) {
    return function(Message\ServerRequestInterface $req, $next) use ($handler) {
        try {
            return $next($req);
        } catch (\Exception $e) {
            return $handler($req, $e);
        }
    };
}
