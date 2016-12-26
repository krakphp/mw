<?php

use Krak\Mw;

require_once __DIR__ . '/../vendor/autoload.php';

class AppendMw
{
    private $c;
    public function __construct($c) {
        $this->c = $c;
    }

    public function handle($s, $next) {
        return $next($s . $this->c);
    }
}

class IdMw {
    public function handle($s) {
        return $s;
    }
}

$handler = mw\compose([
    new IdMw(),
    new AppendMw('b')
], null, mw\methodInvoke('handle'));

assert($handler('a') == 'ab');
