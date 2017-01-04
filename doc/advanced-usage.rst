=============
Avanced Usage
=============

**WARNING: These features have been removed on the v0.3 branch due to backwards compatability issues.**

.. _advanced-usage-custom-invoke:

Custom Invocation
=================

The final argument to ``mw\compose`` is a callable ``$invoke``. It defaults to ``call_user_func`` but can be any callable with the same function signature. This allows you to customize how the middleware is invoked. One specific example of this would be container aware invocation. A middleware instead of being a callable can be reference to a service in the container.

Here's an example of how to use the ``pimpleAwareInvoke``

.. code-block:: php

    <?php

    $c = new Pimple\Container();
    $c['service'] = function() {
        return function() {
            return 1;
        };
    };

    $handler = mw\compose([
        'service',
        function($next) {
            return $next() + 1;
        }
    ], null, mw\pimpleAwareInvoke($c));

    assert(2 == $handler());

Meta Middleware
~~~~~~~~~~~~~~~

Custom invocation is creation is a very useful feature; however, it requires special consideration if you are creating your own Meta Middleware. Meta middleware are middleware that accept other middleware and perform some action with the middleware. ::

    mw\group
    mw\lazy
    mw\filter

These are all meta middleware. To allow custom invocation work work for *all* middleware whever they are added, these meta middleware need to make use of the ``$invoke`` parameter that is passed to all middleware (see :ref:`mw\\compose <advanced-usage-custom-invoke>`).

Here's an example:

.. code-block:: php

    <?php

    // maybe middleware will only invoke the middleware if the parameter is < 10
    function maybe($mw) {
        return function($i, $next, $invoke) use ($mw) {
            if ($i >= 10) {
                return $next($i); // forward to next middleware
            }

            return $invoke($mw, $i, $next, $invoke);
        };
    }

    function loggingInvoke() {
        return function($func, ...$params) {
            echo "Invoking Middleware with Param: $params[0]\n";
            return call_user_func($func, ...$params);
        };
    }

    $handler = mw\compose([
        function() { return 1; },
        maybe(function($i, $next) {
            return $next($i) + 100;
        })
    ], null, loggingInvoke());

    echo $handler(1) . PHP_EOL;
    echo $handler(10) . PHP_EOL;

    /*
    Outputs:

    Invoking Middleware with Param: 1
    Invoking Middleware with Param: 1
    Invoking Middleware with Param: 1
    101
    Invoking Middleware with Param: 10
    Invoking Middleware with Param: 10
    1
    */
