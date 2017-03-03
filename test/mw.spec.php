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
            $stack = mw\stack('stack');
            $stack->push(append('a'), 10);
            $stack->push(append('b'), 5);
            $stack->push(append('0'), 5);
            $stack->push(id())->push(append('c'));
            $stack->pop(5);

            $handler = $stack->compose();
            assert($handler('') == 'abc');
        });
        it('allows shifting and unshifting', function() {
            $stack = mw\stack('stack');
            $stack->unshift(append('b'));
            $stack->unshift(append('a'), 1);
            $stack->unshift(append('c'));
            $stack->unshift(append('d'));
            $stack->shift();
            $stack->unshift(id());

            $handler = $stack->compose();

            assert($handler('') == 'abc');
        });
        it('can add elements before or after other middleware', function() {
            $stack = mw\stack('stack');
            $stack->push(id());
            $stack->push(append('a'));
            $stack->push(append('c'), 0, 'mw');
            $stack->push(append('e'));
            $stack->before('mw', append('b'));
            $stack->after('mw', append('d'));
            $handler = $stack->compose();
            assert($handler('') == 'edcba');
        });
        it('has a name', function() {
            $stack = mw\stack('stack');
            assert($stack->getName() == 'stack');
        });
        it('throws exception if composing on empty', function() {
            try {
                mw\stack('stack')->compose();
                assert(false);
            } catch (RuntimeException $e) {
                assert(strpos($e->getMessage(), 'Middleware stack "stack" is empty') === 0);
            }
        });
        it('throws exception if no middleware resolve', function() {
            try {
                $handler = mw\stack('stack')->push(function($next) { $next(); })->compose();
                $handler();
                assert(false);
            } catch (RuntimeException $e) {
                assert(strpos($e->getMessage(), 'Middleware stack "stack" was not able to return') === 0);
            }
        });
        it('replaces an entry if it is pushed with the same name', function() {
            $stack = mw\stack('stack');
            $stack->push(id())
                ->push(append('a'))
                ->push(append('d'), 0, 'mw')
                ->push(append('c'))
                ->push(append('b'), 0, 'mw');
            $handler = $stack->compose();
            assert($handler('') == 'cba');
        });
        it('allows custom context', function() {
            $stack = mw\stack('stack', [], new Mw\Context\StdContext(function() { return 1; }));
            $stack->push(id());
            $handler = $stack->compose();
            assert($handler(2) === 1);
        });
        it('allows you to change name', function() {
            $stack = mw\stack()->withName('Stack');
            assert($stack->getName() == 'Stack');
        });
        it('allows you to change context', function() {
            $stack = mw\stack('stack')->withContext(new Mw\Context\StdContext(function() { return 1; }));
            $stack->push(id());
            $handler = $stack->compose();
            assert($handler(2) === 1);
        });
        it('allows you to change link class', function() {
            $stack = mw\stack('stack')->withLinkClass(MyLink::class);
            $stack->push(function($id, $next) {
                assert($next instanceof MyLink);
                return;
            });
            $handler = $stack->compose();
            $handler(1);
        });
    });
    describe('#stackMerge', function() {
        it('merges stacks together into a new stack', function() {
            $a = mw\stack('stack', [
                mw\stackEntry(id()),
                mw\stackEntry(append('a')),
                mw\stackEntry(append('b')),
                mw\stackEntry(append('d'), 0, 'mw')
            ]);
            $b = mw\stack('stack', [
                mw\stackEntry(append('c'), 0, 'mw'),
            ]);
            $c = mw\stackMerge($a, $b);
            $handler = $c->compose();
            assert($handler('') == 'cba');
        });
    });
    describe('#pimpleAwareInvoke', function() {
        it('uses container if the mw is a service definition before invoking', function() {
            $c = new \Pimple\Container();
            $c['a'] = function() { return function() {return 'abc';}; };
            $handler = mw\compose([
                'a',
            ], new Mw\Context\StdContext(mw\pimpleAwareInvoke($c)));
            assert('abc' == $handler());
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
    describe('Context\PimpleContext', function() {
        it('allows pimple access via context', function() {
            $container = new Pimple\Container();
            $container['a'] = 1;
            $handler = mw\compose([
                function($v, $next) {
                    return $v + $next->getContext()['a'];
                }
            ], new Mw\Context\PimpleContext($container));
            assert($handler(1) == 2);
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
