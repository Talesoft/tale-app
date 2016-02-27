<?php

namespace Tale\App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\App;

interface PluginInterface
{

    public function invoke(
        App $app,
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    );
}