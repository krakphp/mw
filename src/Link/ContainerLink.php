<?php

namespace Krak\Mw\Link;

use Krak\Mw,
    Psr\Container\ContainerInterface,
    ArrayAccess;

class ContainerLink extends Mw\Link implements ContainerInterface, ArrayAccess
{
    public function get($id) {
        return $this->getContext()->getContainer()->get($id);
    }

    public function has($id) {
        return $this->getContext()->getContainer()->has($id);
    }

    public function offsetGet($id) {
        return $this->get($id);
    }

    public function offsetSet($id, $value) {
        throw new \LogicException('Cannot set offset, this is a read only container');
    }

    public function offsetExists($id) {
        return $this->has($id);
    }

    public function offsetUnset($id) {
        throw new \LogicException('Cannot unset offset, this is a read only container');
    }
}
