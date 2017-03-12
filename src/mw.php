<?php

namespace Krak\Mw;

use RuntimeException,
    SplMinHeap,
    Psr\Container\ContainerInterface;

use function iter\map,
    iter\chain;

/** compose a set of middleware into a handler

    ```
    $handler = mw\compose([
        function($a, $b, $next) {
            return $a . $b . "e";
        },
        function($a, $b, $next) {
            return $next($a . 'b', $b . 'd');
        }
    ]);

    $res = $handler('a', 'c');
    assert($res === 'abcde');
    ```

    @param array $mws The set of middleware to compose
    @param Context|null $ctx The context for the middleware
    @param string $link_class The class to use for linking the middleware
    @return \Closure the composed set of middleware as a handler
*/
function compose(array $mws, Context $ctx = null, $link_class = Link::class) {
    if (!count($mws)) {
        throw new \InvalidArgumentException("Cannot compose an empty set of middleware.");
    }

    $last = new $link_class($mws[0], $ctx ?: new Context\StdContext());
    $head = $last->chains($mws);

    return function(...$params) use ($head) {
        return $head(...$params);
    };
}

/** creates a composer function */
function composer(Context $ctx = null, $link_class = Link::class) {
    $ctx = $ctx ?: new Context\StdContext();
    return function(array $mws) use ($ctx, $link_class) {
        return compose($mws, $ctx, $link_class);
    };
}

/** forces a guard in the composed middleware */
function guardedComposer($composer, $msg) {
    return function(array $mws) use ($composer, $msg) {
        array_unshift($mws, guard($msg));
        return $composer($mws);
    };
}

/** Group a set of middleware into one. This internally just
    calls the compose middleware, so the way the middleware are
    composed works exactly the same

    ```
    $append = function($c) { return function($s, $next) use ($c) { return $next($s . $c); }; };

    $handler = mw\compose([
        function($v) { return $v; },
        $append('d'),
        mw\group([
            $append('c'),
            $append('b'),
        ]),
        $append('a'),
    ]);

    $res = $handler('');
    assert($res === 'abcd');
    ```

    @param array $mws The set of middleware to group.
    @return \Closure a middleware composed of other middleware
*/
function group(array $mws) {
    return function(...$params) use ($mws) {
        list($params, $link) = splitArgs($params);
        $next = $link->chains($mws);
        return $next(...$params);
    };
}

/** Lazily create the middleware once it needs to be executed. This will
    cache the created middleware so that subsequent calls to this middleware
    will use the same generated middleware.

    ```
    $create_mw = function($a) { return function() use ($a) { return $a; }; };
    $val = 0;
    $handler = mw\compose([
        mw\lazy(function() use ($create_mw, &$val){
            $val += 1;
            return $create_mw($val);
        })
    ]);
    assert($handler() == 1 && $handler() == 1);
    ```

    @param callable $mw_gen Creates the middleware
    @return \Closure the middleware
*/
function lazy(callable $mw_gen) {
    return function(...$params) use ($mw_gen) {
        static $mw;
        if (!$mw) {
            $mw = $mw_gen();
        }
        list($params, $link) = splitArgs($params);
        $link = $link->chain($mw);
        return $link(...$params);
    };
}

/** Creates a middleware that will conditionally execute or skip the middleware
    passed in depending on the result of the $predicate

    ```
    $mw = function() { return 2; };
    $handler = mw\compose([
        function() { return 1; },
        mw\filter($mw, function($v) {
            return $v == 4;
        })
    ]);
    assert($handler(5) == 1 && $handler(4) == 2);
    ```
*/
function filter(callable $mw, callable $predicate) {
    return function(...$all_params) use ($mw, $predicate) {
        list($params, $link) = splitArgs($all_params);
        if ($predicate(...$params)) {
            $link = $link->chain($mw);
        }

        return $link(...$params);
    };
}

/** returns the parameters itself. If it's a multi-param middleware, it'll
    return the params as a tuple, else it'll return the single value */
function identity() {
    return function(...$params) {
        return count($params) > 2 ? array_slice($params, 0, -1) : $params[0];
    };
}

/** always return the value passed in */
function stub($ret) {
    return function() use ($ret) {
        return $ret;
    };
}

/** Will throw an error if this middleware is every reached. Designed to be a failsafe
    and provide a helpful error message when a composed stack of middleware fail to
    return a result. */
function guard($msg) {
    return function() use ($msg) {
        throw new Exception\NoResultException($msg);
    };
}

/** invokes a middleware checking if the mw is a service defined in a PSR Container */
function containerAwareInvoke(ContainerInterface $c, $invoke = 'call_user_func') {
    return function($func, ...$params) use ($c, $invoke) {
        if (is_string($func) && $c->has($func)) {
            $func = $c->get($func);
        }

        return $invoke($func, ...$params);
    };
}

function methodInvoke($method, $allow_callable = true, $invoke = 'call_user_func') {
    return function($func, ...$params) use ($method, $invoke, $allow_callable) {
        if (is_object($func) && method_exists($func, $method)) {
            return $invoke([$func, $method], ...$params);
        } else if ($allow_callable && is_callable($func)) {
            return $invoke($func, ...$params);
        }

        $msg = "Middleware cannot be invoked because it does not contain the '$method' method";
        if ($allow_callable) {
            $msg .= ' and is not a callable.';
        }

        throw new \LogicException($msg);
    };
}

/** utility method for splitting the parameters into the params and the next */
function splitArgs(array $args) {
    return [array_slice($args, 0, -1), end($args)];
}

function stack(array $entries = []) {
    return new Stack($entries);
}
