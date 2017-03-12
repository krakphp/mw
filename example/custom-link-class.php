<?php

use Krak\Mw;

require_once __DIR__ . '/../vendor/autoload.php';

class LoggingLink extends Mw\Link
{
    public function log($info) {
        echo $info . PHP_EOL;
    }
}

$handler = mw\compose([
    function($i, $next) {
        $next->log('hi');
        return 1;
    }
], null, LoggingLink::class);

assert($handler(0) == 1);
