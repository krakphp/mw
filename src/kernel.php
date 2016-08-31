<?php

namespace Krak\Mw;

use Psr\Http\Message;

interface HttpKernel {
    /** @return Message\ResponseInterface */
    public function __invoke(Message\ServerRequestInterface $req);
}

const ORDER_FIFO = 'fifo';
const ORDER_LIFO = 'lifo';

class MwStackHttpKernel implements HttpKernel
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

    public function __invoke(Message\ServerRequestInterface $req) {
        $kernel = mwkHttpKernel($this->mws, ORDER_LIFO);
        return $kernel($req);
    }
}

/** this is the main kernel which transforms a set of middleware into a resolveable
    kernel */
function mwHttpKernel(array $mws, $order = ORDER_FIFO) {
    if ($order == ORDER_FIFO) {
        $mws = array_reverse($mws);
    }
    if (!count($mws)) {
        throw new \Exception('Cant resolve an empty set of middleware');
    }

    return function(Message\ServerRequestInterface $req) use ($mws) {
        $mw = composeMwSet($mws, function(Message\ServerRequestInterface $req) {
            throw new \Exception('All middleware executed and no response was returned');
        });

        return $mw($req);
    };
}

function mockHttpKernel(Message\ResponseInterface $resp) {
    return function(Message\ServerRequestInterface $req) use ($resp) {
        return $resp;
    };
}
