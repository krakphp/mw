<?php

use Krak\Mw;

require_once __DIR__ . '/../vendor/autoload.php';

function rot13() {
    return function($s) {
        return str_rot13($s);
    };
}

function wrapInner($v) {
    return function($s, $next) use ($v) {
        return $next($v . $s . $v);
    };
}
function wrapOuter($v) {
    return function($s, $next) use ($v) {
        return $v . $next($s) . $v;
    };
}

$handler = mw\compose([
    rot13(),
    wrapInner('o'),
    wrapOuter('a'),
]);

echo $handler('p') . PHP_EOL;
// abcba
