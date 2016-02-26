<?php

namespace Tale\App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface MiddlewareInterface
{

    public function handleRequest(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    );
}