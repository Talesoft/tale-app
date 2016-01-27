<?php

namespace Tale\Runtime;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\Http\Factory;
use Tale\Http\Response;
use Tale\Http\ServerRequest;

class App implements MiddlewareInterface
{

    private $_middlewares;

    public function __construct()
    {

        $this->_middlewares = [];
    }

    public function with(callable $middleware)
    {

        if (!is_callable($middleware))
            throw new \InvalidArgumentException(
                "Argument 1 passed to App->with needs to be valid callback"
            );

        $app = clone $this;
        $app->_middlewares[] = $middleware;

        return $app;
    }

    public function dispatch(
        ServerRequestInterface $request,
        ResponseInterface $response
    )
    {

        $current = 0;
        $next = function(
            ServerRequestInterface $request,
            ResponseInterface $response
        ) use(&$current, &$next) {

            if ($current >= count($this->_middlewares))
                return $response;

            $middleware = $this->_middlewares[$current++];
            return call_user_func($middleware, $request, $response, $next);
        };

        return $next($request, $response);
    }

    public function run()
    {

        return $this->dispatch(Factory::getServerRequest(), new Response());
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    )
    {

        $response = $this->dispatch($request, $response);
        return $next($request, $response);
    }
}