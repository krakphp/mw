<?php

use Krak\Mw;

require_once __DIR__ . '/../vendor/autoload.php';

$container = new Pimple\Container();
$container['i'] = 5;
$container['inc_mw'] = function() {
    return function($i, $next) {
        return $next($i + 1);
    };
};

$handler = mw\compose([
    function($i) { return $i; },
    function($i, $next) {
        $ctx = $next->getContext();
        return $next($i + $ctx['i']);
    },
    'inc_mw'
], new Mw\Context\PimpleContext($container));

assert($handler(4) == 10);
