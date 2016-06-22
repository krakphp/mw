<?php

namespace Krak\Mw\Symfony;

use Symfony\Bridge\PsrHttpMessage;

/** simple tuple for holding the psr http bridge factories */
class HttpMessageBridge
{
    public $hf_factory;
    public $hm_factory;

    public function __construct(
        PsrHttpMessage\HttpFoundationFactoryInterface $hf_factory = null,
        PsrHttpMessage\HttpMessageFactoryInterface $hm_factory = null
    ) {
        $this->hf_factory = $hf_factory ?: new PsrHttpMessage\Factory\HttpFoundationFactory();
        $this->hm_factory = $hm_factory ?: new PsrHttpMessage\Factory\DiactorosFactory();
    }
}
