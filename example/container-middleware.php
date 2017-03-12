<?php

use Krak\Mw;
use Krak\Cargo;

require_once __DIR__ . '/../vendor/autoload.php';

$container = Cargo\container();
$container['i'] = 5;
$container['inc_mw'] = function() {
    return function($i, $next) {
        return $next($i + 1);
    };
};

$handler = mw\compose([
    function($i) { return $i; },
    function($i, $next) {
        $c = $next->getContext()->getContainer();
        return $next($i + $c->get('i'));
    },
    'inc_mw'
], new Mw\Context\ContainerContext($container->toInterop()));

assert($handler(4) == 10);
