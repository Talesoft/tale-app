<?php

namespace Tale\App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\App;

/**
 * Class PluginTrait
 * @package Tale\App
 */
trait PluginTrait
{

    /**
     * @var App
     */
    private $_app = null;
    /**
     * @var ServerRequestInterface
     */
    private $_request = null;
    /**
     * @var ResponseInterface
     */
    private $_response = null;
    /**
     * @var callable
     */
    private $_next = null;

    /**
     * @return App
     */
    protected function getApp()
    {
        return $this->_app;
    }

    /**
     * @return ServerRequestInterface
     */
    protected function getRequest()
    {
        return $this->_request;
    }

    /**
     * @param ServerRequestInterface $request
     * @return $this
     */
    protected function setRequest(ServerRequestInterface $request)
    {

        $this->_request = $request;

        return $this;
    }

    /**
     * @return ResponseInterface
     */
    protected function getResponse()
    {
        return $this->_response;
    }

    /**
     * @param ResponseInterface $response
     * @return $this
     */
    protected function setResponse(ResponseInterface $response)
    {

        $this->_response = $response;

        return $this;
    }

    /**
     * @return ResponseInterface
     */
    protected function next()
    {

        return call_user_func($this->_next, $this->_request, $this->_response);
    }

    /**
     * @return ResponseInterface
     */
    protected function handle() {

        return $this->next();
    }

    /**
     * @param App                    $app
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function invoke(
        App $app,
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    )
    {

        $this->_app = $app;
        $this->_request = $request;
        $this->_response = $response;
        $this->_next = $next;

        return $this->handle();
    }
}