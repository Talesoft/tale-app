<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

include '../../vendor/autoload.php';

$app = new Tale\Runtime\App();

$app->with(function(
    ServerRequestInterface $request,
    ResponseInterface $response,
    callable $next
) {

    $request = $request->withAttribute('someAttribute', 'some value');

    $response = $response->withHeader('Content-Type', 'text/plain');
    $response->getBody()->write('Some Output from App!');

    return $next($request, $response, $next);
});



$response = $app->dispatch();
$response->emit();