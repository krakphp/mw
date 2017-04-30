<?php

namespace Krak\Mw\Context;

use Krak\Mw\Context;
use Krak\Invoke;

class StdContext implements Context
{
    private $invoke;

    public function __construct($invoke = null) {
        $this->invoke = $invoke ?: new Invoke\CallableInvoke();
    }

    public function getInvoke() {
        return $this->invoke;
    }
}
