<?php

namespace Krak\Mw;

use Psr\Http\Message\ResponseInterface;

interface ResponseFactory {
    /** @return ResponseInterface */
    public function __invoke();
}

/** returns a default response, always */
function defaultResponseFactory(ResponseInterface $resp) {
    return function() use ($resp) {
        return $resp;
    };
}

/** returns a guzzle psr7 response */
function guzzleResponseFactory() {
    return function() {
        return new \GuzzleHttp\Psr7\Response();
    };
}

/** returns a zend diactoros response */
function diactorosResponseFactory() {
    return function() {
        return new \Zend\Diactoros\Response();
    };
}
