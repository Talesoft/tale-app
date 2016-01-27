<?php

namespace Tale\Runtime;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\Http\Factory;
use Tale\Http\Response;
use Tale\Http\ServerRequest;

/**
 * Class App
 * @package Tale\Runtime
 */
class App implements MiddlewareInterface
{

    /**
     * @var callable[]
     */
    private $_middlewares;

    /**
     *
     */
    public function __construct()
    {

        $this->_middlewares = [];
    }

    /**
     * @param callable $middleware
     *
     * @return App
     */
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

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface|mixed
     */
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

    /**
     * @return ResponseInterface|mixed
     */
    public function run()
    {

        return $this->dispatch(Factory::getServerRequest(), new Response());
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface|mixed
     */
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