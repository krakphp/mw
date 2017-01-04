<?php

use Krak\Mw;

require_once __DIR__ . '/../vendor/autoload.php';

// maybe middleware will only invoke the middleware if the parameter is < 10
function maybe($mw) {
    return function($i, $next) use ($mw) {
        if ($i < 10) {
            /** NOTE - this is the crucial part where we prepend the `$mw` onto the link. Now, when we execute `$next`,
                the `$mw` func will be first to be executed */
            $next = $next->chain($mw);
        }

        return $next($i);
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
], new Mw\Context\StdContext(loggingInvoke()));

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
