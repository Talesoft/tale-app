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
use Tale\Http\Runtime\MiddlewareInterface;
use Tale\Http\Runtime\Queue;

/**
 * Class App
 * @package Tale\Runtime
 */
class App implements MiddlewareInterface, ContainerInterface, ConfigurableInterface, ServiceInterface
{
    use ContainerTrait;
    use ConfigurableTrait;
    use ServiceTrait;

    private $_environment;

    /**
     * @var Queue
     */
    private $_middlewares;

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

        $this->_middlewares = new Queue();

        foreach ($this->getOption('middlewares') as $middleware)
            $this->useMiddleware($middleware);

        foreach ($this->getOption('services') as $service)
            $this->useService($service);
    }

    public function __clone()
    {

        $this->_middlewares = clone $this->_middlewares;
    }

    /**
     * @param callable $middleware
     *
     * @return $this
     */
    public function useMiddleware($middleware)
    {

        $this->_middlewares->enqueue($middleware);

        return $this;
    }

    /**
     * @param string $className
     *
     * @return $this
     */
    public function useService($className)
    {

        if (!is_subclass_of($className, ServiceInterface::class))
            throw new \InvalidArgumentException(
                "Failed to add service $className: ".
                "Service is not a valid ".ServiceInterface::class." instance"
            );

        $this->register($className);
        return $this->useMiddleware(function($request, $response, $next) use ($className) {

            /** @var ServiceInterface $service */
            $service = $this->get($className);
            return $service($request, $response, $next);
        });
    }

    public function dispatch(
        ServerRequestInterface $request = null,
        ResponseInterface $response = null
    )
    {

        return Runtime::dispatch($this->_middlewares, $request, $response);
    }

    public function display(
        ServerRequestInterface $request = null,
        ResponseInterface $response = null
    )
    {

        Runtime::emit($this->_middlewares, $request, $response);
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

    /**
     * @implements MiddlewareInterface->__invoke
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     * @param callable                                 $next
     *
     * @return mixed
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    )
    {

        return $next($request, $this->dispatch($request, $response));
    }
}