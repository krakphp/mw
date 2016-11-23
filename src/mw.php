<?php

namespace Krak\Mw;

use RuntimeException,
    SplMinHeap;

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
    @param callable $last The final handler in case no middleware resolves the arguments
    @return \Closure the composed set of middleware as a handler
*/
function compose(array $mws, $last = null) {
    $last = $last ?: function() {
        throw new RuntimeException("Last middleware was reached. No handlers were found.");
    };

    return array_reduce($mws, function($acc, $mw) {
        return function(...$params) use ($acc, $mw) {
            $params[] = $acc;
            return $mw(...$params);
        };
    }, $last);
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
        list($params, $next) = _splitArgs($params);

        $handle = compose($mws, $next);
        return $handle(...$params);
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
function lazy($mw_gen) {
    return function(...$params) use ($mw_gen) {
        static $mw;
        if (!$mw) {
            $mw = $mw_gen();
        }

        return $mw(...$params);
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
function filter($mw, $predicate) {
    return function(...$all_params) use ($mw, $predicate) {
        list($params, $next) = _splitArgs($all_params);
        if ($predicate(...$params)) {
            return $mw(...$all_params);
        }

        return $next(...$params);
    };
}

/** higher the sort, the sooner it will execute in the stack */
function stackEntry($mw, $sort = 0, $name = null) {
    return [$mw, $sort, $name];
}

function stack(array $entries = []) {
    return MwStack::createFromEntries($entries);
}

/** merges multiple stacks together into a new stack */
function stackMerge(...$stacks) {
    /** merge stacks together */
    $entries = chain(...map(function($stack) {
        return $stack->getEntries();
    }, $stacks));

    return MwStack::createFromEntries($entries);
}

function _splitArgs($params) {
    $next = end($params);
    return [array_slice($params, 0, -1), $next];
}

function _filterHeap(SplMinHeap $heap, $predicate) {
    $new_heap = new SplMinHeap();
    foreach ($heap as $v) {
        if ($predicate($v)) {
            $new_heap->insert($v);
        }
    }
    return $new_heap;
}
