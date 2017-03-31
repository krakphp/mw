<?php

use Krak\Mw;

function append($c) {
    return function($s, $next) use ($c) {
        return $next($s . $c);
    };
}

function id() {
    return function($x) {
        return $x;
    };
}

class ArrayContainer implements Psr\Container\ContainerInterface
{
    private $data;
    public function __construct(array $data) {
        $this->data = $data;
    }

    public function get($id) {
        return $this->data[$id];
    }
    public function has($id) {
        return array_key_exists($id, $this->data);
    }
}

class AppendMw
{
    private $c;
    public function __construct($c) {
        $this->c = $c;
    }

    public function handle($s, $next) {
        return $next($s . $this->c);
    }
}

class IdMw {
    public function handle($s) {
        return $s;
    }
}

class MyLink extends Mw\Link {}

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
    describe('#composer', function() {
        it('creates a compose function', function() {
            $compose = mw\composer(new Mw\Context\StdContext(), MyLink::class);
            $handler = $compose([
                function($s, $next) {
                    return $next instanceof MyLink;
                },
                function($s, $next) {
                    return $next($s);
                },
            ]);
            assert($handler('a'));
        });
    });
    describe('#group', function() {
        it('groups a set of middleware into one middleware', function() {
            $handler = mw\compose([
                id(),
                append('d'),
                mw\group([
                    append('c'),
                    append('b'),
                ]),
                append('a'),
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
    describe('#splitArgs', function() {
        it('splits args between params and middleware', function() {
            list($args, $next) = mw\splitArgs([1,2,3]);
            assert($args == [1,2] && $next == 3);
        });
    });
    describe('Stack', function() {
        it('maintains a stack of middleware with priority', function() {
            $stack = mw\stack();
            $stack->push(append('a'), 10);
            $stack->push(append('b'), 5);
            $stack->push(append('0'), 5);
            $stack->push(id())->push(append('c'));
            $stack->pop(5);

            $handler = mw\compose([$stack]);
            assert($handler('') == 'abc');
        });
        it('allows shifting and unshifting', function() {
            $stack = mw\stack();
            $stack->unshift(append('b'));
            $stack->unshift(append('a'), 1);
            $stack->unshift(append('c'));
            $stack->unshift(append('d'));
            $stack->shift();
            $stack->unshift(id());

            $handler = mw\compose([$stack]);

            assert($handler('') == 'abc');
        });
        it('can add elements before or after other middleware', function() {
            $stack = mw\stack();
            $stack->push(id(), -1);
            $stack->push(append('a'));
            $stack->push(append('c'), 0, 'mw');
            $stack->push(append('e'));
            $stack->before('mw', append('b'));
            $stack->after('mw', append('d'));
            $handler = mw\compose([$stack]);
            assert($handler('') == 'decab');
        });
        it('replaces an entry if it is pushed with the same name', function() {
            $stack = mw\stack();
            $stack->push(append('a'))
                ->push(append('d'), 0, 'mw')
                ->unshift(append('c'))
                ->unshift(id())
                ->push(append('b'), 0, 'mw');
            $handler = mw\compose([$stack]);
            assert($handler('') == 'bac');
        });
        it('can retrieve a named entry', function() {
            $stack = mw\stack();
            $stack->on('a', function() {})
                ->on('b', function() {});
            $entry = $stack->get('b');
            assert($entry[0] instanceof Closure && $entry[1] === 0 && $entry[2] === 'b');
        });
        it('can move an entry to the top of its stack', function() {
            $stack = mw\stack();
            $stack->push(append('a'), 0, 'append_a')
                ->push(append('b'))
                ->toTop('append_a');
            $handler = mw\compose([id(), $stack]);
            assert($handler('') == 'ab');
        });
        it('can move an entry to the bottom of its stack', function() {
            $stack = mw\stack();
            $stack->push(append('a'))
                ->push(append('b'), 0, 'append_b')
                ->toBottom('append_b');
            $handler = mw\compose([id(), $stack]);
            assert($handler('') == 'ab');
        });
    });
    describe('#methodInvoke', function() {
        it('will invoke a specific method instead of using a callable', function() {
            $handler = mw\compose([
                new IdMw(),
                new AppendMw('b')
            ], new Mw\Context\StdContext(mw\methodInvoke('handle', false)));

            assert($handler('a') == 'ab');
        });
        it('will allow mixed callable and methods', function() {
            $handler = mw\compose([
                id(),
                new AppendMw('b')
            ], new Mw\Context\StdContext(mw\methodInvoke('handle', true)));

            assert($handler('a') == 'ab');
        });
        it('will throw an exception if it cannot invoke', function() {
            $handler = mw\compose([
                id(),
                new StdClass(),
                new AppendMw('b')
            ], new Mw\Context\StdContext(mw\methodInvoke('handle')));

            try {
                $handler('a');
                assert(false);
            } catch (LogicException $e) {
                assert(true);
            }
        });
    });
    describe('Context\ContainerContext', function() {
        it('allows container access via context', function() {
            $container = new ArrayContainer([
                'a' => 1,
            ]);
            $compose = mw\composer(new Mw\Context\ContainerContext($container), Mw\Link\ContainerLink::class);
            $handler = $compose([
                function($v, $next) {
                    return $v + $next['a'];
                }
            ]);
            assert($handler(1) == 2);
        });
    });
});
