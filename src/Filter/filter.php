<?php

namespace Krak\Mw\Filter;

use Psr\Http\Message\RequestInterface;

function _cmp($pattern, $path, $use_re = true) {
    return $use_re
        ? preg_match($pattern, $path)
        : strcmp($pattern, $path) == 0;
}

function path($pattern, $use_re = true) {
    return function(RequestInterface $req) use ($pattern, $use_re) {
        $path = $req->getUri()->getPath();
        return _cmp($pattern, $path, $use_re);
    };
}

/** check on the header, if no pattern is provided, this checks if the header
    exists. If the value is provided, it gets the header value and does a
    comparison */
function header($header_name, $pattern = null, $use_re = true) {
    return function(RequestInterface $req) use ($header_name, $pattern, $use_re) {
        $header_values = $req->getHeader($header_name);

        if (!$value) {
            return count($header_values) > 0;
        }

        foreach ($header_values as $value) {
            if (_cmp($pattern, $value, $use_re)) {
                return true;
            }
        }

        return false;
    };
}

function opOr(array $filters) {
    return function(RequestInterface $req) use ($filters) {
        return array_reduce($filters, function($acc, $filter) use ($req) {
            return $acc || $filter($req);
        }, false);
    };
}

function opAnd(array $filters) {
    return function(RequestInterface $req) use ($filters) {
        return array_reduce($filters, function($acc, $filter) use ($req) {
            return $acc && $filter($req);
        }, true);
    };
}

function opNot($filter) {
    return function(RequestInterface $req) use ($filter) {
        return !$filter($req);
    };
}
