API
===

The api documentation is broken up into 2 parts: Middleware documentation and Middleware Stack documentation.

.. _api-middleware-functions:

Middleware Functions
~~~~~~~~~~~~~~~~~~~~

Closure compose(array $mws, Context $ctx = null, callable $last = null, $link_class = Link::class)
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

    ``$last`` represents the last middleware to be executed if no other middleware handle the parameters. This typically will throw an exception in that case, but it might be advantageous to set this to something else for your needs.

    ``$link_class`` is the class that will be constructed for the middleware link. It must be or extend ``Krak\Mw\Link`` (see: :doc:`advanced-usage` for more details).

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

Closure pimpleAwareInvoke(Pimple\\Container $c, $invoke = 'call_user_func')
    invokes middleware while checking if the mw is a service defined in the pimple container

Closure methodInvoke(string $method_name, $allow_callable = true, $invoke = 'call_user_func')
    This will convert the middleware into a callable array like ``[$obj, $method_name]`` and invoke it. The ``$allow_callable`` parameter will allow the stack to either invoke objects with the given method or invoke callables. If you want to only allow objects with that method to be invokable, then set ``$allow_callable`` to ``false``.

Stack Functions
~~~~~~~~~~~~~~~

MwStack stack($name, array $entries = [], Context $ctx = null, $link_class = Link::class)
    Creates a MwStack instance. Every stack must have a name which is just a personal identifier for the stack. It's primary use is for errors/exceptions that help the user track down which stack has an issue. ``$ctx`` and ``$link_class`` are forwarded to the MwStack constructor.

    .. code-block:: php

        <?php

        $stack = mw\stack('demo stack');
        $stack->push($mw)
            ->unshift($mw1);

        // compose into handler
        $handler = $stack->compose();
        // or, use as a grouped middleware
        $handler = mw\compose([
            $mw2,
            $stack
        ]);

array stackEntry(callable $mw, $sort = 0, $name = null)
    Creates an entry for the MwStack. This is only used if you want to initialize a stack with entries, else, you'll just be using the stack methods to create stack entries.

    .. code-block:: php

        <?php

        $stack = mw\stack('demo stack', [
            stackEntry($mw1, 0, 'mw1'),
            stackEntry($mw2),
            stackEntry($mw3, 5, 'mw3'),
        ]);
        // equivalent to
        $stack = mw\stack('demo stack')
            ->push($mw1, 0, 'mw1')
            ->push($mw2)
            ->push($mw3, 5, 'mw3');

MwStack stackMerge(...$stacks)
    Merges stacks into one another. The resulting stack has the same name as the first stack in the set. The values from the later stacks will override the values from the earlier stacks.

    .. code-block:: php

        <?php

        $a = mw\stack('stack', [
            mw\stackEntry($mw1),
            mw\stackEntry($mw2),
            mw\stackEntry($mw3, 0, 'mw')
        ]);
        $b = mw\stack('stack', [
            mw\stackEntry($mw4, 0, 'mw'),
        ]);
        $c = mw\stackMerge($a, $b);
        // stack $c is equivalent to
        $c = mw\stack('stack')
            ->push($mw1)
            ->push($mw2)
            ->push($mw4, 0, 'mw')

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


class MwStack implements Countable
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The stack presents a mutable interface into a stack of middleware. Middleware can be added with a name and priority. Only one middleware with a given name may exist. Middleware that are last in the stack will be executed first once the stack is composed.

__construct($name, Context $ctx = null, $link_class = Link::class)
    Creates the mw stack with a name. The ``$ctx`` and ``$link_class`` are forwarded to ``mw\compose`` once the stack is composed.
string getName()
    returns the name of the middleware
MwStack push($mw, $sort = 0, $name = null)
    Pushes a new middleware on the stack. The sort determines the priority of the middleware. Middleware pushed at the same priority will be pushed on like a stack.
MwStack unshift($mw, $sort = 0, $name = null)
    Similar to push except it prepends the stack at the beginning.
MwStack on($name, $mw, $sort = 0)
    Simply an alias of ``push``; however, the argument order lends it nicer for adding/replacing named middleware.
MwStack before($name, $mw, $mw_name = null)
    Inserts a middleware right before the given middleware.
MwStack after($name, $mw, $mw_name = null)
    Inserts a middleware right after the given middleware.
array shift($sort = 0)
    Shifts the stack at the priority given by taking an element from the front/bottom of the stack. The shifted stack entry is returned as a tuple.
array pop($sort = 0)
    Pops the stack at the priority given be taking an element from the back/top of the stack. The popped stack entry is returned as a tuple.
array remove($name)
    Removes a named middleware. The removed middleware is returned as a tuple.
array normalize()
    Normalizes the stack into an array of middleware that can be used with ``mw\compose``
mixed __invoke(...$params)
    Allows the middleware stack to be used as middleware itself.
Closure compose(callable $last = null)
    Composes the stack into a handler.
Generator getEntries()
    Yields the raw stack entries in the order they were added.
MwStack withContext(Context $ctx)
    Creates a clone of the current stack with an updated context
MwStack withLinkClass($class)
    Creates a clone of the current stack with an updated link class
MwStack withEntries($entries)
    Creates a clone of the current stack with updated entries.
MwStack static createFromEntries($name, $entries)
    Creates a stack with a set of entries. ``mw\stack`` internally calls this.

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

interface Context
~~~~~~~~~~~~~~~~~

Represents the middleware context utilized by the internal system.

getInvoke()
    Returns the invoker configured for this context.

class Context\\StdContext implements Context
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The default context for the mw system. It simply holds the a value to the invoker for custom invocation.

__construct($invoke = 'call_user_func')

class Context\\PimpleContext implements Context
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Provides nice pimple integeration by allowing the context to act like a pimple container and it provides pimple invocation by default.

View the :doc:`cookbook/pimple-middleware` for example on this.

__construct(Container $container, $invoke = null)
    The pimple contianer and an optional invoker if you don't want to use the ``pimpleAwareInvoke``
