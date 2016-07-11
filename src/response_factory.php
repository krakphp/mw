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

/** json decorator which will json encode the data and set the json content type */
function jsonResponseFactory($rf, $encode_opts = 0) {
    return function($status = 200, array $headers = [], $body = null) use ($rf, $encode_opts) {
        return $rf($status, $headers, json_encode($body, $encode_opts))
            ->withHeader('Content-Type', 'application/json');
    };
}

/** html decorator which will set the text/html content-type on the response */
function htmlResponseFactory($rf) {
    return function($status = 200, array $headers = [], $body = null) use ($rf) {
        return $rf($status, $headers, $body)->withHeader('Content-Type', 'text/html');
    };
}

/** text decorator which will set the text/plain content-type on the response */
function textResponseFactory($rf) {
    return function($status = 200, array $headers = [], $body = null) use ($rf) {
        return $rf($status, $headers, $body)->withHeader('Content-Type', 'text/plain');
    };
}
