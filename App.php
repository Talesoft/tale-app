<?php

namespace Tale;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\App\MiddlewareInterface;
use Tale\App\MiddlewareTrait;
use Tale\Http\Factory;
use Tale\Http\Response;
use Tale\Runtime\Middleware\Queue;

/**
 * Class App
 * @package Tale\Runtime
 */
class App implements MiddlewareInterface
{
    use MiddlewareTrait;

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
     * @param callable $middleware
     *
     * @return App
     */
    public function add($middleware)
    {

        $this->_middlewares->enqueue($middleware);

        return $this;
    }

    public function invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    )
    {

        return $this->_middlewares->invoke($request, $response);
    }

    public function run()
    {

        return $this->invoke(Factory::getServerRequest(), new Response());
    }
}