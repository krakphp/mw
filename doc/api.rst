API
===

The api documentation is broken up into 2 parts: Middleware documentation and Middleware Stack documentation.

.. _api-middleware-functions:

Middleware Functions
~~~~~~~~~~~~~~~~~~~~

Closure compose(array $mws, Context $ctx = null, $link_class = Link::class)
    Composes a set of middleware into a handler.

    .. code-block:: php

        <?php

        $handler = mw\compose([
            function($a, $b, $next) {
                return $a . $b;
            },
            function($a, $b, $next) {
                return $next($a . 'b', $b . 'd');
            }
        ]);

        $res = $handler('a', 'c');
        assert($res === 'abcd');

    The middleware stack passed in is executed in LIFO order. So the last middleware will be executed first, and the first middleware will be executed last.

    After composing the stack of middleware, the resulting handler will share the same signature as the middleware except that it **won't** have the ``$next``.

    ``$ctx`` will default to ``Context\StdContext`` if none is supplied, and it will be the context that is passed to the start link (see: :doc:`advanced-usage` for more details).

    ``$link_class`` is the class that will be constructed for the middleware link. It must be or extend ``Krak\Mw\Link`` (see: :doc:`advanced-usage` for more details).

Closure composer(Context $ctx, $link_class = Link::class)
    Creates a composer function that accepts a set of middleware and composes a handler.

    .. code-block:: php

        <?php

        $compose = mw\composer();
        $handler = $compose([
            mw1(),
            mw2()
        ]);

Closure guardedComposer($composer, $msg)

    Creates a composer that will automatically append a guard middleware with the given message when composing.

    .. code-block:: php

        $compose = mw\composer();
        $compose = mw\guardedComposer($compose, 'No result was returned.');
        $handler = $compose([]);
        $handler();
        // A NoResultException will be thrown with the `No result was returned.` message

Closure group(array $mws)
    Creates a new *middleware* composed as one from a middleware stack.

    Internally, this calls the ``compose`` function, so the same behaviors will apply to this function.

    .. code-block:: php

        <?php

        /** some middleware that will append values to the parameter */
        function appendMw($c) {
            return function($s, $next) use ($c) {
                return $next($s . $c);
            };
        }

        $handler = mw\compose([
            function($s) { return $s; },
            append('d'),
            mw\group([
                append('c'),
                append('b'),
            ]),
            append('a'),
        ]);

        $res = $handler('');
        assert($res === 'abcd');

    On the surface, this doesn't seemv very useful, but the ability group middleware into one allows you to then apply other middleware onto a group.

    For example, you can do something like: ::

        $grouped = mw\group([
            // ...
        ]);
        mw\filter($grouped, $predicate);

    In this example, we just filted an entire group of middleware

Closure lazy(callable $mw_gen)
    Lazily creates and executes middleware when it's executed. Useful if the middleware needs to be generated from a container or if it has expensive dependencies that you only want initialized if the middleware is going to be executed.

    .. code-block:: php

        <?php

        $mw = lazy(function() {
            return expensiveMw($expensive_service_that_was_just_created);
        });

    The expensive service won't be created until the `$mw` is actually executed

Closure filter(callable $mw, callable $predicate)
    Either applies the middleware or skips it depending on the result of the predicate. This if very useful for building conditional middleware.

    .. code-block:: php

        <?php

        $mw = function() { return 2; };
        $handler = mw\compose([
            function() { return 1; },
            mw\filter($mw, function($v) {
                return $v == 4;
            })
        ]);
        assert($handler(5) == 1 && $handler(4) == 2);

    In this example, the stack of middleware always returns 1, however, the filtered middleware gets executed if the value is 4, and in that case, it returns 2 instead.


Invoke Functions
~~~~~~~~~~~~~~~~

Closure containerAwareInvoke(Psr\\Container\\ContainerInterface $c, $invoke = 'call_user_func')
    invokes middleware while checking if the mw is a service defined in the psr container.

Closure methodInvoke(string $method_name, $allow_callable = true, $invoke = 'call_user_func')
    This will convert the middleware into a callable array like ``[$obj, $method_name]`` and invoke it. The ``$allow_callable`` parameter will allow the stack to either invoke objects with the given method or invoke callables. If you want to only allow objects with that method to be invokable, then set ``$allow_callable`` to ``false``.

Stack Functions
~~~~~~~~~~~~~~~

Stack stack(array $entries = [])
    Creates a Stack instance. This is an alias of the ``Stack::__construct``

    .. code-block:: php

        <?php

        $stack = mw\stack([
            $mw1,
            $mw2
        ])->unshift($mw0);

Utility Functions
~~~~~~~~~~~~~~~~~

array splitArgs(array $args)
    Splits arguments between the parameters and middleware.

    .. code-block:: php

        <?php

        use Krak\Mw

        function middleware() {
            return function(...$args) {
                list($args, $next) = Mw\splitArgs($args);
                return $next(...$args);
            };
        }


class Stack
~~~~~~~~~~~

The stack presents a mutable interface into a stack of middleware. Middleware can be added with a name and priority. Only one middleware with a given name may exist. Middleware that are last in the stack will be executed first once the stack is composed.

__construct(array $entries = [])
    Creates the stack and will ``fill`` it with the given entries.
Stack fill($entries)
    Pushes each entry onto the stack in the order defined.
Stack push($mw, $sort = 0, $name = null)
    Pushes a new middleware on the stack. The sort determines the priority of the middleware. Middleware pushed at the same priority will be pushed on like a stack.
Stack unshift($mw, $sort = 0, $name = null)
    Similar to push except it prepends the stack at the beginning.
Stack on($name, $mw, $sort = 0)
    Simply an alias of ``push``; however, the argument order lends it nicer for adding/replacing named middleware.
Stack before($name, $mw, $mw_name = null)
    Inserts a middleware right before the given middleware.
Stack after($name, $mw, $mw_name = null)
    Inserts a middleware right after the given middleware.
array shift($sort = 0)
    Shifts the stack at the priority given by taking an element from the front/bottom of the stack. The shifted stack entry is returned as a tuple.
array pop($sort = 0)
    Pops the stack at the priority given be taking an element from the back/top of the stack. The popped stack entry is returned as a tuple.
array remove($name)
    Removes a named middleware. The removed middleware is returned as a tuple.
array toArray()
    Normalizes the stack into an array of middleware that can be used with ``mw\compose``
mixed __invoke(...$params)
    Allows the middleware stack to be used as middleware itself.

class Link
~~~~~~~~~~

Represents a link in the middleware chain. A link instance is passed to every middleware as the last parameter which allows the next middleware to be called. See :doc:`advanced-usage` for more details.

__construct($mw, Context $ctx, Link $next = null)
    Creates a link. If ``$next`` is provided, then the created link will be the new head of that linked list.
__invoke(...$params)
    Invokes the middleware. It forwards the params to the middleware and additionaly adds the next link to the end of argument list for the middleware.
chain($mw)
    Creates a new link to be the head of the current list of links. The context is copied from the current link.
getContext()
    returns the context instance apart of the link.

class Link\\ContainerLink
~~~~~~~~~~~~~~~~~~~~~~~~~

Extends the Link class and implements the Psr\\Container\\ContainerInterface and ArrayAccess. Keep in mind that it offers read-only access, so setting and deleting offsets will cause an exception to be thrown.

interface Context
~~~~~~~~~~~~~~~~~

Represents the middleware context utilized by the internal system.

getInvoke()
    Returns the invoker configured for this context.

class Context\\StdContext implements Context
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The default context for the mw system. It simply holds the a value to the invoker for custom invocation.

__construct($invoke = 'call_user_func')

class Context\\ContainerContext implements Context
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Provides psr container integeration by allowing the context to act like a psr container and it provides container invocation by default.

View the :doc:`cookbook/container-middleware` for example on this.

__construct(ContainerInterface $container, $invoke = null)
    The psr container and an optional invoker if you don't want to use the ``containerAwareInvoke``
