<?php

namespace Tale\App\Plugin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\App;
use Tale\App\PluginInterface;
use Tale\Http\Runtime\MiddlewareInterface;

class Middleware implements MiddlewareInterface
{

    /**
     * @var App
     */
    private $_app;
    private $_className;

    public function __construct(App $app, $className)
    {

        if (!is_a($className, PluginInterface::class, true))
            throw new \InvalidArgumentException(
                "Failed to create plugin middleware: $className ".
                "is not a valid ".PluginInterface::class." subclass"
            );

        $this->_app = $app;
        $this->_className = $className;

        $this->_app->register($className);
    }

    /**
     * @return App
     */
    public function getApp()
    {
        return $this->_app;
    }

    /**
     * @return mixed
     */
    public function getClassName()
    {
        return $this->_className;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    )
    {
        /** @var PluginInterface $plugin */
        $plugin = $this->_app->getPlugin($this->_className);
        return $plugin->invoke(
            $this->_app,
            $request,
            $response,
            $next
        );
    }
}