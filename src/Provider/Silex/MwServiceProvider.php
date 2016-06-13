<?php

namespace Krak\Mw\Provider\Silex;

use Krak\Mw,
    Pimple\Container,
    Silex\Application,
    Pimple\ServiceProviderInterface,
    Silex\Api\BootableProviderInterface,
    Symfony\Component\HttpFoundation\Request;

class MwServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app) {
        $app['krak.mw.middleware'] = null;
        $app['krak.mw.order'] = Mw\ORDER_FIFO;

        $app['krak.mw.resolve'] = function(Container $app) {
            $mws = $app['krak.mw.middleware'];
            return Mw\mw_resolve($mws ?: [], $app['krak.mw.order']);
        };
    }

    public function boot(Application $app) {
        $app->before(function(Request $req, Application $app) {
            $resolve = $app['krak.mw.resolve'];
            list($final_req, $resp) = $resolve($req);

            if ($resp) {
                return $resp;
            }

            if ($final_req !== $req) {
                throw new \LogicException('Resolved request !== initial request. Silex expects requests to be mutable.');
            }
        });
    }
}
