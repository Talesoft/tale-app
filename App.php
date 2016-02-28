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

    private $_environment;

    /**
     * @var Queue
     */
    private $_middlewareQueue;

    /**
     * @param array $options
     */
    public function __construct(array $options = null)
    {

        $this->_environment = new Environment($options);

        $this->defineOptions([
            'middlewares' => [],
        ], $this->_environment->getOptions());

        $this->interpolateOptions();
        $this->registerSelf();

        $this->_middlewareQueue = new Queue();

        foreach ($this->getOption('middlewares') as $middleware)
            $this->append($middleware);
    }

    public function __clone()
    {

        $this->_middlewareQueue = clone $this->_middlewareQueue;
    }

    /**
     * @return \Tale\App\Environment
     */
    public function getEnvironment()
    {
        return $this->_environment;
    }

    /**
     * @return Queue
     */
    public function getMiddlewareQueue()
    {
        return $this->_middlewareQueue;
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

        $this->_middlewareQueue->append($this->prepareMiddleware($middleware));

        return $this;
    }

    /**
     * @param callable $middleware
     *
     * @return $this
     */
    public function prepend($middleware)
    {

        $this->_middlewareQueue->prepend($this->prepareMiddleware($middleware));

        return $this;
    }

    public function run(
        ServerRequestInterface $request = null,
        ResponseInterface $response = null
    )
    {

        return Runtime::run($this->_middlewareQueue, $request, $response);
    }

    public function display(
        ServerRequestInterface $request = null,
        ResponseInterface $response = null
    )
    {

        Http::emit($this->run($request, $response));
    }

    /**
     * @overrides MiddlewareTrait->handleRequest
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function handleRequest()
    {

        return $this->handleNext(null, $this->run(
            $this->getRequest(),
            $this->getResponse()
        ));
    }
}