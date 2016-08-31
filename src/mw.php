<?php

namespace Krak\Mw;

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/kernel.php';
require_once __DIR__ . '/response_factory.php';

use Psr\Http\Message,
    Krak\HttpMessage\Match;

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

function mount($path, $mw, $cmp = Match\CMP_SW) {
    return filter($mw, Match\path($path, $cmp));
}

function on() {
    $args = func_get_args();
    if (count($args) < 2) {
        throw new \InvalidArgumentException('Expected at least 2 arguments');
    }

    if (count($args) == 2) {
        $method = 'GET';
        $cmp = Match\CMP_EQ;
        list($path, $mw) = $args;
    } else if (count($args) == 3) {
        $cmp = Match\CMP_EQ;
        list($method, $path, $mw) = $args;
    } else {
        list($method, $path, $cmp, $mw) = $args;
    }

    return filter($mw, Match\route($method, $path, $cmp));
}

/** this will create a middleware that is composed together as one middleware. */
function compose($mws, $order = ORDER_FIFO) {
    if ($order == ORDER_FIFO) {
        $mws = array_reverse($mws);
    }

    return function(Message\ServerRequestInterface $req, $next) use ($mws) {
        $mw = composeMwSet($mws, $next);
        return $mw($req);
    };
}

/** lazily create the middleware once it needs to be executed */
function lazy($mw_gen) {
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
