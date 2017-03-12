<?php

namespace Krak\Mw;

/** Represents a link in the middleware chain. A link instance is passed to every middleware
    as the last parameter which allows the next middleware to be called */
class Link
{
    private $mw;
    private $next;
    private $ctx;

    public function __construct($mw, Context $ctx, Link $next = null) {
        $this->mw = $mw;
        $this->ctx = $ctx;
        $this->next = $next;
    }

    public function __invoke(...$params) {
        if (!$this->next) {
            throw new Exception\NoResultException('Cannot invoke last middleware in chain. No middleware returned a result.');
        }
        $mw = $this->mw;
        $invoke = $this->ctx->getInvoke();
        $params[] = $this->next;
        return $invoke($mw, ...$params);
    }

    /** Chains a middleware to the current link */
    public function chain($mw) {
        return new static($mw, $this->ctx, $this);
    }

    public function chains(array $mw) {
        return array_reduce($mw, function($acc, $mw) {
            return $acc->chain($mw);
        }, $this);
    }


    public function getContext() {
        return $this->ctx;
    }
}
