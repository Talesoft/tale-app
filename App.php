<?php

namespace Tale;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\App\MiddlewareInterface;
use Tale\App\MiddlewareTrait;
use Tale\Http\Emitter;
use Tale\Http\Factory;
use Tale\Http\Response;
use Tale\App\Middleware\Queue;

/**
 * Class App
 * @package Tale\Runtime
 */
class App implements MiddlewareInterface
{

    /**
     * @var Queue
     */
    private $_middlewares;

    /**
     *
     */
    public function __construct()
    {

        $this->_middlewares = new Queue();
    }

    public function __clone()
    {

        $this->_middlewares = clone $this->_middlewares;
    }

    /**
     * @param MiddlewareInterface $middleware
     *
     * @return $this
     */
    public function useMiddleware(MiddlewareInterface $middleware)
    {

        $this->_middlewares->enqueue($middleware);

        return $this;
    }

    public function handleRequest(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    )
    {

        return $this->_middlewares->handleRequest($request, $response, $next);
    }

    public function dispatch(
        ServerRequestInterface $request = null,
        ResponseInterface $response = null
    )
    {

        $request = $request ?: Http::getServerRequest();
        $response = $response ?: new Response();

        return $this->_middlewares->dispatch($request, $response);
    }

    public function display(
        ServerRequestInterface $request = null,
        ResponseInterface $response = null
    )
    {

        Http::emit($this->dispatch($request, $response));
    }
}