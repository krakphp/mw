<?php

namespace Krak\Mw\Provider\Stack;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\HttpKernelInterface;

class StackMwKernel implements HttpKernelInterface
{
    private $kernel;
    private $resolve;

    public function __construct($kernel, $resolve) {
        $this->kernel = $kernel;
        $this->resolve = $resolve;
    }

    public function handle(Request $req, $type = self::MASTER_REQUEST, $catch = true) {
        list($req, $resp) = call_user_func($this->resolve, $req);
        if ($resp) {
            return $resp;
        }

        return $this->kernel->handle($req, $type, $catch);
    }
}
