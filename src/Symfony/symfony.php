<?php

namespace Krak\Mw\Symfony;

use Psr\Http\Message\ServerRequestInterface,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\HttpKernelInterface,
    Symfony\Component\HttpKernel\TerminableInterface;

function symfonyKernelMw(
    HttpKernelInterface $kernel,
    &$final_req,
    HttpMessageBridge $bridge = null
) {
    $bridge = $bridge ?: new HttpMessageBridge();

    return function(ServerRequestInterface $req, $next) use ($kernel, &$final_req, $bridge) {
        $hf_req = $bridge->hf_factory->createRequest($req);
        $hf_resp = $kernel->handle($hf_req);

        $final_req = $hf_req;

        return $bridge->hm_factory->createResponse(
            $kernel->handle($bridge->hf_factory->createRequest($req))
        );
    };
}

function symfonyApp(HttpKernelInterface $symfony_kernel, &$final_req, HttpMessageBridge $bridge = null, $request_factory = null) {
    $bridge = $bridge ?: new HttpMessageBridge();
    $request_factory = $request_factory ?: function() { return Request::createFromGlobals(); };

    return function($kernel) use ($symfony_kernel, &$final_req, $bridge, $request_factory) {
        $req = $bridge->hm_factory->createRequest($request_factory());
        $hf_resp = $bridge->hf_factory->createResponse($kernel($req));

        $hf_resp->send();

        if ($symfony_kernel instanceof TerminableInterface && $final_req) {
            $symfony_kernel->terminate($final_req, $hf_resp);
        }
    };
}

function symfonyFactory(HttpKernelInterface $kernel, HttpMessageBridge $bridge = null, $request_factory = null) {
    $final_req = null;
    return [
        symfonyKernelMw($kernel, $final_req, $bridge),
        symfonyApp($kernel, $final_req, $bridge, $request_factory)
    ];
}
