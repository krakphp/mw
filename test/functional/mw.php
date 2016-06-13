<?php

function onA404() {
    return function($req, $next) {
        if ($req->getRequestUri() == '/a') {
            return new Symfony\Component\HttpFoundation\Response('not found', 404);
        }

        return $next($req);
    };
}
