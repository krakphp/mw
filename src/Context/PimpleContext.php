<?php

namespace Krak\Mw\Context;

use Pimple\Container,
    Krak\Mw;

class PimpleContext implements \ArrayAccess, Mw\Context
{
    private $container;
    private $invoke;

    public function __construct(Container $container, $invoke = null) {
        $this->container = $container;
        $this->invoke = $invoke ?: Mw\pimpleAwareInvoke($container);
    }

    public function getInvoke() {
        return $this->invoke;
    }

    public function offsetSet($offset, $value) {
        return $this->container->offsetSet($offset, $value);
    }

    public function offsetGet($offset) {
        return $this->container->offsetGet($offset);
    }

    public function offsetExists($offset) {
        return $this->container->offsetExists($offset);
    }

    public function offsetUnset($offset) {
        return $this->container->offsetUnset($offset);
    }

    public function getContainer() {
        return $this->container;
    }
}
