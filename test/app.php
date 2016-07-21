<?php

use Krak\Mw;

describe('#silentFailApp', function() {
    it('catches exceptions and silently finishes', function() {
        $app = mw\silentFailApp(function() {
            throw new Exception('Error');
        });
        $app(function() {});
        assert(true);
    });
});
