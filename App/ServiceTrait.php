<?php

namespace Tale\App;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tale\App;

/**
 * Class ServiceTrait
 * @package Tale\App
 */
trait ServiceTrait
{

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
     * @return callable
     */
    protected function getNext()
    {

        return $this->_next;
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function next(RequestInterface $request = null, ResponseInterface $response = null)
    {

        if ($request)
            $this->_request = $request;

        if ($response)
            $this->_response = $response;

        return call_user_func($this->_next, $this->_request, $this->_response);
    }

    /**
     * @return ResponseInterface
     */
    protected function handle() {

        return $this->next();
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    )
    {

        $this->_request = $request;
        $this->_response = $response;
        $this->_next = $next;

        $response = $this->handle();

        $this->_request = null;
        $this->_response = null;
        $this->_next = null;

        return $response;
    }
}