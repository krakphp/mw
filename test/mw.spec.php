<?php

use Krak\Mw,
    Krak\HttpMessage\Match,
    GuzzleHttp\Psr7;

describe('Mw', function() {
    describe('#on', function() {
        beforeEach(function() {
            $this->req = new Psr7\ServerRequest('GET', '/api');
        });
        it('creates a filtered mw by the path', function() {
            $mw = mw\on('/api', function() {
                return 'a';
            });
            assert('a' == $mw($this->req, function() {}));
        });
        it('creates a filtered mw by the method and path', function() {
            $mw = mw\on('POST', '/api', function() {
                return 'a';
            });
            assert('b' == $mw($this->req, function() { return 'b'; }));
        });
        it('creates a filtered mw by the method, path, and cmp', function() {
            $mw = mw\on('GET', '~^/a*~', match\CMP_RE, function() {
                return 'a';
            });
            assert('a' == $mw($this->req, function() { return 'b'; }));
        });
    });
    describe('#mount', function() {
        it('mounts an mw on url prefix', function() {
            $mw = mw\mount('/api', function($req) {
                return $req->getUri()->getPath();
            });
            assert('/api/user' == $mw(
                new Psr7\ServerRequest('GET', '/api/user'),
                function() {}
            ));
        });
    });
    describe('HttpApp', function() {
        require_once __DIR__ . '/app.php';
    });
});
