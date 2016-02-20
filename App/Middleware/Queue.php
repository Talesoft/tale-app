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
    use MiddlewareTrait;

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface|mixed
     */
    public function invoke(
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
}