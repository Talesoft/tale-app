<?php

namespace Tale\App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SplQueue;
use Tale\App\MiddlewareInterface;
use Tale\App\MiddlewareTrait;

/**
 * Class Queue
 * @package Tale\Runtime
 */
class Queue extends SplQueue implements MiddlewareInterface
{

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

        $queue = clone $this;

        $next = function(
            ServerRequestInterface $request,
            ResponseInterface $response
        ) use(&$next, $queue) {

            if (count($queue) < 1)
                return $response;

            $middleware = $queue->dequeue();
            return call_user_func($middleware, $request, $response, $next);
        };

        return $next($request, $response);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable $next
     *
     * @return ResponseInterface|mixed
     */
    public function handleRequest(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    )
    {

        return $next($request, $this->dispatch($request, $response));
    }
}