<?php

namespace Krak\Mw\Context;

use Psr\Container\ContainerInterface,
    Krak\Mw;

class ContainerContext implements Mw\Context
{
    private $container;
    private $invoke;

    public function __construct(ContainerInterface $container, $invoke = null) {
        $this->container = $container;
        $this->invoke = $invoke ?: Mw\containerAwareInvoke($container);
    }

    public function getInvoke() {
        return $this->invoke;
    }

    public function getContainer() {
        return $this->container;
    }
}
