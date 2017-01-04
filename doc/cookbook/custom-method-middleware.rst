========================
Custom Method Middleware
========================

**WARNING: This feature has been removed on the v0.3 branch due to backwards compatability issues.**

If you want to use middleware that are class based and use a method other than ``__invoke``, you need to use the ``methodInvoke`` invoker.

Here's an example using classes with a method of ``handle``

.. code-block:: php

    <?php

    use Krak\Mw;

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

    $handler = mw\compose([
        new IdMw(),
        new AppendMw('b')
    ], null, mw\methodInvoke('handle'));

    assert($handler('a') == 'ab');