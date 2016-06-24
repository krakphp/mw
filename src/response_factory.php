<?php

namespace Krak\Mw;

use Psr\Http\Message\ResponseInterface;

interface ResponseFactory {
    /** @return ResponseInterface */
    public function __invoke($status = 200, array $headers = [], $body = null);
}

/** returns a default response, always */
function defaultResponseFactory(ResponseInterface $resp) {
    return function($status = 200, array $headers = [], $body = null) use ($resp) {
        return $resp;
    };
}

/** returns a guzzle psr7 response */
function guzzleResponseFactory() {
    return function($status = 200, array $headers = [], $body = null) {
        return new \GuzzleHttp\Psr7\Response($status, $headers, $body);
    };
}

/** returns a zend diactoros response */
function diactorosResponseFactory() {
    return function($status = 200, array $headers = [], $body = null) {
        if (is_string($body)) {
            $stream = new \Zend\Diactoros\Stream('php://temp', 'r+');
            if ($body !== '') {
                $stream->write($body);
                $stream->rewind();
            }
            $body = $stream;
        }
        $body = $body === null ? 'php://memory' : $body;
        return new \Zend\Diactoros\Response($body, $status, $headers);
    };
}
