<?php

use Krak\Mw;

describe('Mw', function() {
    describe('#compose', function() {
        it('composes a set of middleware into a handler', function() {
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
        });
    });
    describe('#group', function() {
        it('groups a set of middleware into one middleware', function() {
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
        });
    });
    describe('#filter', function() {
        it('applies a filter if the predicate is met', function() {
            $mw = function() { return 2; };
            $handler = mw\compose([
                function() { return 1; },
                mw\filter($mw, function($v) {
                    return $v == 4;
                })
            ]);
            assert($handler(5) == 1 && $handler(4) == 2);
        });
    });
    describe('#lazy', function() {
        it('lazily creates the middleware to be executed', function() {
            $create_mw = function($a) { return function() use ($a) { return $a; }; };
            $val = 0;
            $handler = mw\compose([
                mw\lazy(function() use ($create_mw, &$val){
                    $val += 1;
                    return $create_mw($val);
                })
            ]);
            assert($handler() == 1 && $handler() == 1);
        });
    });
    describe('#_filterHeap', function() {
        it('filters a min heap', function() {
            $heap = new SplMinHeap();
            $heap->insert(3);
            $heap->insert(2);
            $heap->insert(1);

            $heap = mw\_filterHeap($heap, function($v) { return $v != 2; });
            $vals = iterator_to_array($heap);
            assert(current($vals) == 1 && end($vals) == 3);
        });
    });
    describe('MwStack', function() {
        it('maintains a stack of middleware with priority', function() {
            $stack = mw\stack();
            $stack->push('a', 10);
            $stack->push('b', 5);
            $stack->push('d', 5);
            $stack->push('c');
            $stack->pop(5);

            $res1 = implode($stack->normalize());
            $res2 = implode($stack->normalize());
            assert($res1 == 'cba' && $res1 == $res2);
        });
        it('allows for named middleware', function() {
            $stack = mw\stack();
            $stack->push('a', 0, 'first');
            $stack->push('b', 1, 'first');

            $res = implode($stack->normalize());
            assert($res == 'b');
        });
        it('allows shifting and unshifting', function() {
            $stack = mw\stack();
            $stack->unshift('b');
            $stack->unshift('c', 1);
            $stack->unshift('a');
            $stack->unshift('d');
            $stack->shift();

            $res = implode($stack->normalize());
            assert($res == 'abc');
        });
        it('can add elements before or after other middleware', function() {
            $stack = mw\stack();
            $stack->push('b', 0, 'mw');
            $stack->before('mw', 'a');
            $stack->after('mw', 'c');
            $res = implode($stack->normalize());
            assert($res == 'abc');
        });
    });
    describe('#stackMerge', function() {
        it('merges stacks together into a new stack', function() {
            $a = mw\stack([
                mw\stackEntry('a'),
                mw\stackEntry('b'),
                mw\stackEntry('d', 0, 'mw')
            ]);
            $b = mw\stack([
                mw\stackEntry('c', 0, 'mw'),
            ]);
            $c = mw\stackMerge($a, $b);
            $res = implode($c->normalize());
            assert($res == 'abc');
        });
    });
});
