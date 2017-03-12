=============
Avanced Usage
=============

.. _advanced-usage-context:

Context
=======

Each middleware is invoked with a ``Mw\Context`` instance. This is responsible for holding additional data to be used internally within the mw system *and* to provide additional features/usage for users. The context is available via the ``Mw\Link`` object of the middleware.

.. code-block:: php

    <?php

    use Krak\Mw;

    $handle = Mw\compose([
        function($v, Mw\Link $next) {
            $ctx = $next->getContext();
            return 1;
        }
    ], new Mw\Context\StdContext());

You can configure or pass in any context as long as it implements the ``Mw\Context`` interface. Currently, the context provides an invoker via the ``getInvoke`` method. This allows custom invocation of the middleware as shown in the :doc:`cookbook`.

Custom Invocation
=================

You can provide custom invocation of the middleware via the context. An invoker is any function that shares the signature of ``call_user_func``. Its sole purpose is to invoke functions with their parameters. With custom invocation, you can do cool things like have middleware as pimple identifiers.

Link
====

The final argument to each middleware is an instance of ``Mw\Link``. The link is represents the link/chain between middlewares. Technically speaking, it's a singly-linked list of middleware that once executed will invoke the entire chain of middleware.

The link is responsible for building a set of middleware via the ``chain``.

.. code-block:: php

    <?php

    use Krak\Mw;

    $link = new Mw\Link(function($i) {
        return $i * 2;
    }, new Mw\Context\StdContext());
    $link = $link->chain(function($i, $next) {
        return $next($i) + 1;
    });
    assert($link(2) == 5);

``chain`` takes a middleware and produces a new link that is appened to the head of the linked list of mw links. As you can see, the middleware on the second link is executed first.

Meta Middleware
===============

Custom Context and invocation is a very useful feature; however, it requires special consideration if you are creating your own Meta Middleware. Meta middleware are middleware that accept other middleware and inject middleware into the chain of middleware. ::

    mw\group
    mw\lazy
    mw\filter

These are all meta middleware. To allow *all* middleware to be properly linked and have access to the context, these meta middleware need to learn how to properly inject middleware into the mw link.

Here's an example:

.. code-block:: php

    <?php

    use Krak\Mw;

    // maybe middleware will only invoke the middleware if the parameter is < 10
    function maybe($mw) {
        return function($i, $next) use ($mw) {
            if ($i < 10) {
                /** NOTE - this is the crucial part where we prepend the `$mw` onto the link. Now, when we execute `$next`,
                    the `$mw` func will be first to be executed */
                $next = $next->chain($mw);
            }

            return $next($i);
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
    ], new Mw\Context\StdContext(loggingInvoke()));

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
