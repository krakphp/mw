<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Krak\Mw;

$stack = mw\stack('Stack Name');
$stack->push(function($a, $next) {
    return $next($a . 'b');
})
->push(function($a, $next) {
    return $next($a) . 'z';
}, 0, 'c')
// replace the c middleware
->on('c', function($a, $next) {
    return $next($a) . 'c';
})
->before('c', function($a, $next) {
    return $next($a) . 'x';
})
->after('c', function($a, $next) {
    return $next($a) . 'y';
})
// this goes on first
->unshift(function($a, $next) {
    return $a;
});

$handler = $stack->compose();
$res = $handler('a');
assert($res == 'abxcy');
