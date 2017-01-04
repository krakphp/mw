=================
Pimple Middleware
=================

You can easily integrate your middleware stacks with pimple using the ``Mw\Context\PimpleContext`` which will allow any middleware to be a pimple identifier and give you access to your pimple container via the mw context.

.. code-block:: php

    <?php

    use Krak\Mw;

    $container = new Pimple\Container();
    $container['i'] = 5;
    $container['inc_mw'] = function() {
        return function($i, $next) {
            return $next($i + 1);
        };
    };

    $handler = mw\compose([
        function($i) { return $i; },
        function($i, $next) {
            $ctx = $next->getContext();
            return $next($i + $ctx['i']);
        },
        'inc_mw'
    ], new Mw\Context\PimpleContext($container));

    assert($handler(4) == 10);
