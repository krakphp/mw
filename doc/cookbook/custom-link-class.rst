=================
Custom Link Class
=================

You can optionally provide your own ``Link`` to allow easier access to the Context or other methods. It's best to extend the ``Mw\Link`` and just add methods and *not* data because the Link class logic is very critical to the design of the mw system.

.. code-block:: php

    <?php

    use Krak\Mw;

    class LoggingLink extends Mw\Link
    {
        public function echoLog($info) {
            echo $info . PHP_EOL;
        }
        public function log() {
            return $this->getContext()->logger;
        }
    }

    $handler = mw\compose([
        function($i, $next) {
            $next->echoLog('hi');
            return 1;
        }
    ], null, null, LoggingLink::class);

    assert($handler(0) == 1);
