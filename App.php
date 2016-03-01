<?php

namespace Tale;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\App\Environment;
use Tale\Di\Container\DedicatedTrait;
use Tale\Di\ContainerInterface;
use Tale\Http\Runtime;
use Tale\Http\Runtime\Middleware\Queue;
use Tale\Http\Runtime\MiddlewareInterface;
use Tale\Http\Runtime\MiddlewareTrait;

/**
 * Class App
 * @package Tale\Runtime
 */
class App implements ContainerInterface, ConfigurableInterface, MiddlewareInterface
{
    use DedicatedTrait;
    use ConfigurableTrait;
    use MiddlewareTrait;

    private $environment;

    /**
     * @var Queue
     */
    private $queue;

    /**
     * @param array $options
     */
    public function __construct(array $options = null)
    {

        $this->environment = new Environment($options);

        $this->defineOptions([
            'middleware' => [],
        ], $this->environment->getOptions());

        $this->interpolateOptions();
        $this->registerSelf();

        $this->queue = new Queue();

        foreach ($this->options['middleware'] as $middleware)
            $this->append($middleware);
    }

    public function __clone()
    {

        $this->queue = clone $this->queue;
    }

    /**
     * @return Environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return Queue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    public function prepareMiddleware($middleware)
    {

        if (is_string($middleware)) {

            if (function_exists($middleware))
                return $middleware;

            if (!class_exists($middleware))
                throw new \RuntimeException(
                    "Failed to register middleware: Class $middleware doesnt exist"
                );

            if (!$this->has($middleware))
                $this->register($middleware);

            return function($request, $response, $next) use ($middleware) {

                /** @var MiddlewareInterface $mw */
                $mw = $this->get($middleware);
                return $mw($request, $response, $next);
            };
        }

        return $middleware;
    }

    /**
     * @param callable $middleware
     *
     * @return $this
     */
    public function append($middleware)
    {

        $this->queue->append($this->prepareMiddleware($middleware));
        return $this;
    }

    /**
     * @param callable $middleware
     *
     * @return $this
     */
    public function prepend($middleware)
    {

        $this->queue->prepend($this->prepareMiddleware($middleware));
        return $this;
    }

    public function run(
        ServerRequestInterface $request = null,
        ResponseInterface $response = null
    )
    {

        return Runtime::run($this->queue, $request, $response);
    }

    public function display(
        ServerRequestInterface $request = null,
        ResponseInterface $response = null
    )
    {

        Http::emit($this->run($request, $response));
    }

    /**
     * @param callable $next
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function handleRequest(callable $next)
    {

        return $next($this->request, $this->response);
    }
}