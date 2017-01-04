<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Krak\Mw;

$link = new Mw\Link(function($i) {
    return $i * 2;
}, new Mw\Context\StdContext());
$link = $link->chain(function($i, $next) {
    return $next($i) + 1;
});
assert($link(2) == 5);
