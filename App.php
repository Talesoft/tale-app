<?php

namespace Tale;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\App\Environment;
use Tale\App\Plugin\Middleware;
use Tale\App\PluginInterface;
use Tale\App\PluginTrait;
use Tale\Di\ContainerInterface;
use Tale\Di\ContainerTrait;
use Tale\Http\Response;
use Tale\Http\Runtime\MiddlewareInterface;
use Tale\Http\Runtime\Queue;

/**
 * Class App
 * @package Tale\Runtime
 */
class App implements MiddlewareInterface, ContainerInterface, ConfigurableInterface, PluginInterface
{
    use ContainerTrait;
    use ConfigurableTrait;
    use PluginTrait;

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
            'plugins' => []
        ], $this->_environment->getOptions());

        $this->interpolateOptions();

        $this->_middlewareQueue = new Queue();

        foreach ($this->getOption('middlewares') as $middleware)
            $this->useMiddleware($middleware);

        foreach ($this->getOption('plugins') as $plugin)
            $this->usePlugin($plugin);
    }

    public function __clone()
    {

        $this->_middlewareQueue = clone $this->_middlewareQueue;
    }

    /**
     * @param callable $middleware
     *
     * @return $this
     */
    public function useMiddleware($middleware)
    {

        $this->_middlewareQueue->enqueue($middleware);

        return $this;
    }

    /**
     * @param string $className
     *
     * @return $this
     */
    public function usePlugin($className)
    {

        return $this->useMiddleware(new Middleware($this, $className));
    }

    public function dispatch(
        ServerRequestInterface $request = null,
        ResponseInterface $response = null
    )
    {

        $request = $request ?: Http::getServerRequest();
        $response = $response ?: new Response();

        return $this->_middlewareQueue->dispatch($request, $response);
    }

    public function display(
        ServerRequestInterface $request = null,
        ResponseInterface $response = null
    )
    {

        Http::emit($this->dispatch($request, $response));
    }

    protected function handle()
    {

        $this->setResponse($this->dispatch(
            $this->getRequest(),
            $this->getResponse()
        ));

        return $this->next();
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    )
    {

        return $this->_middlewareQueue->__invoke($request, $response, $next);
    }
}