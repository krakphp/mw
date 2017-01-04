<?php

namespace Krak\Mw\Context;

use Krak\Mw\Context;

class StdContext implements Context
{
    private $invoke;

    public function __construct($invoke = 'call_user_func') {
        $this->invoke = $invoke;
    }

    public function getInvoke() {
        return $this->invoke;
    }
}
