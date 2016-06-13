<?php

namespace Krak\Mw;

use Symfony\Component\HttpFoundation\Request;

const ORDER_FIFO = 'fifo';
const ORDER_LIFO = 'lifo';

interface Middleware {
    /** @param Request $req
        @param \Closure $next a func that takes in a request object
    */
    public function __invoke(Request $req, $next);
}

/** Resolves a request into a modified request response tuple */
interface MwResolve {
    public function __invoke(Request $req);
}

class MwStackResolve implements MwResolve
{
    private $mws;

    public function __construct($mws = []) {
        $this->mws = $mws;
    }

    public function pushMw($mw) {
        array_push($this->mws, $mw);
    }
    public function popMw() {
        return array_pop($this->mws);
    }

    public function __invoke(Request $req) {
        $resolve = mw_resolve($this->mws, ORDER_LIFO);
        return $resolve($req);
    }
}

/** Takes a set of middleware and executes them on the request. The algorithm
    for resolving the `$next` requires the $mws to be reversed if you want FIFO
    execution. */
function mw_resolve(array $mws, $order = ORDER_FIFO) {
    if ($order == ORDER_FIFO) {
        $mws = array_reverse($mws);
    }

    return function(Request $req) use ($mws) {
        if (count($mws) == 0) {
            return [$req, null];
        }

        $mw = array_pop($mws);

        $resolved_req;
        $resolve_request = function(Request $req) use (&$resolved_req) {
            $resolved_req = $req;
        };

        $next = array_reduce($mws, function($acc, $mw) {
            return function(Request $req) use ($acc, $mw) {
                return $mw($req, $acc);
            };
        }, $resolve_request);

        $resp = $mw($req, $next);
        return [$resolved_req, $resp];
    };
}

function mock_mw_resolve($res) {
    return function(Request $req) use ($res) {
        return $res;
    };
}
