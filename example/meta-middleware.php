<?php

use Krak\Mw;

require_once __DIR__ . '/../vendor/autoload.php';

// **WARNING: These features have been removed on the v0.3 branch due to backwards compatability issues.**

// maybe middleware will only invoke the middleware if the parameter is < 10
function maybe($mw) {
    return function($i, $next, $invoke) use ($mw) {
        if ($i >= 10) {
            return $next($i); // forward to next middleware
        }

        return $invoke($mw, $i, $next, $invoke);
    };
}

function loggingInvoke() {
    return function($func, ...$params) {
        echo "Invoking Middleware with Param: $params[0]\n";
        return call_user_func($func, ...$params);
    };
}

$handler = mw\compose([
    function() { return 1; },
    maybe(function($i, $next) {
        return $next($i) + 100;
    })
], null, loggingInvoke());

echo $handler(1) . PHP_EOL;
echo $handler(10) . PHP_EOL;

/*
Outputs:

Invoking Middleware with Param: 1
Invoking Middleware with Param: 1
Invoking Middleware with Param: 1
101
Invoking Middleware with Param: 10
Invoking Middleware with Param: 10
1
*/
