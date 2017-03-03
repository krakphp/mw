====================
Container Middleware
====================

You can easily integrate your middleware stacks with any PSR container using the ``Mw\Context\Container`` which will allow any middleware to be a container identifier and give you access to your container via the mw context.

.. code-block:: php

    <?php

    use Krak\Mw;

    $container['i'] = 5;
    $container['inc_mw'] = function() {
        return function($i, $next) {
            return $next($i + 1);
        };
    };

    $compose = Mw\composer(Mw\Context\ContainerContext($container), Mw\Link\ContainerLink::class);

    $handler = $compose([
        function($i) { return $i; },
        function($i, $next) {
            return $next($i + $next['i']);
        },
        'inc_mw'
    ]);

    assert($handler(4) == 10);
