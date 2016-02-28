<?php

namespace Tale;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\App\Environment;
use Tale\App\ServiceInterface;
use Tale\App\ServiceTrait;
use Tale\Di\ContainerInterface;
use Tale\Di\ContainerTrait;
use Tale\Http\Runtime;
use Tale\Http\Runtime\Queue;

/**
 * Class App
 * @package Tale\Runtime
 */
class App implements ContainerInterface, ConfigurableInterface, ServiceInterface
{
    use ContainerTrait;
    use ConfigurableTrait;
    use ServiceTrait;

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
            'services' => []
        ], $this->_environment->getOptions());

        $this->interpolateOptions();
        $this->registerContainer();

        $this->_middlewareQueue = new Queue();

        foreach ($this->getOption('middlewares') as $middleware)
            $this->append($middleware);

        foreach ($this->getOption('services') as $service)
            $this->appendService($service);
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

    /**
     * @param callable $middleware
     *
     * @return $this
     */
    public function append($middleware)
    {

        $this->_middlewareQueue->append($middleware);

        return $this;
    }

    /**
     * @param callable $middleware
     *
     * @return $this
     */
    public function prepend($middleware)
    {

        $this->_middlewareQueue->prepend($middleware);

        return $this;
    }

    /**
     * @param string $className
     *
     * @return $this
     */
    public function appendService($className)
    {

        return $this->append($this->createServiceMiddleware($className));
    }

    /**
     * @param string $className
     *
     * @return $this
     */
    public function prependService($className)
    {

        return $this->prepend($this->createServiceMiddleware($className));
    }

    public function createServiceMiddleware($className)
    {

        if (!is_subclass_of($className, ServiceInterface::class))
            throw new \InvalidArgumentException(
                "Failed to add service $className: ".
                "Service is not a valid ".ServiceInterface::class." instance"
            );

        $this->register($className);
        return function($request, $response, $next) use ($className) {

            /** @var ServiceInterface $service */
            $service = $this->get($className);
            return $service($request, $response, $next);
        };
    }

    public function dispatch(
        ServerRequestInterface $request = null,
        ResponseInterface $response = null
    )
    {

        return Runtime::dispatch($this->_middlewareQueue, $request, $response);
    }

    public function display(
        ServerRequestInterface $request = null,
        ResponseInterface $response = null
    )
    {

        Runtime::emit($this->_middlewareQueue, $request, $response);
    }

    /**
     * @overrides ServiceTrait->handle
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function handle()
    {

        return $this->next(null, $this->dispatch(
            $this->getRequest(),
            $this->getResponse()
        ));
    }
}