Usage
=====

Here's an example of basic usage of the mw library

.. code-block:: php

    <?php

    use Krak\Mw;

    function sum() {
        return funciton($a, $b, $next) {
            return $a + $b;
        };
    }

    function modifyBy($value) {
        return function($a, $b, $next) use ($value) {
            return $next($a + $value, $b);
        };
    }

    $sum = mw\compose([
        sum(),
        modifyBy(1),
    ]);

    $res = $sum(1, 2);
    // $res = 4

The first value in the array is executed last; the last value is executed first. ::

    1,2 -> modifyBy(1) -> 2,2 -> sum() -> 4 -> modifyBy(1) -> 4

Each middleware shares the same format: ::

    function($arg1, $arg2, ..., $next);

A list of arguments, with a final argument $next which is the next middleware function to execute in the stack of middleware.

You can have 0 to n number of arguments. Every middleware needs to share the same signature. Composing a stack of middleware will return a handler which has the same signature as the middleware, but without the `$next` function.

Stack
~~~~~

The library also comes with a MwStack that allows you to easily build a set of middleware.

.. code-block:: php


    <?php

    use Krak\Mw;

    $stack = mw\stack('Stack Name');
    $stack->push(function($a, $next) {
        return $next($a . 'b');
    })
    ->push(function($a, $next) {
        return $next($a) . 'c';
    }, 0, 'c')
    // this goes on first
    ->unshift(function($a, $next) {
        return $a;
    }))
    ->before('c', function($a, $next) {
        return $next($a) . 'x';
    })
    ->after('c', function($a, $next) {
        return $next($a) . 'y';
    });

    $handler = $stack->compose();
    $res = $handler('a');
    // $res = abxcy
