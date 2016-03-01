<?php

namespace Tale\Test;

use Psr\Http\Message\ResponseInterface;
use Tale\App;
use Tale\Http\Runtime\MiddlewareInterface;
use Tale\Http\Runtime\MiddlewareTrait;
use Tale\Stream\StringStream;

class Model implements MiddlewareInterface
{
    use MiddlewareTrait;

    public function getData()
    {

        return '{Data from Model}';
    }
}

class Database implements MiddlewareInterface
{
    use MiddlewareTrait;

    public function getData()
    {

        return '{Data from Database}';
    }
}

class Renderer implements MiddlewareInterface
{
    use MiddlewareTrait;

    public function render(array $data)
    {

        return implode(', ', $data);
    }

    protected function handleRequest(callable $next)
    {

        return $next($this->request, $this->response->withStatus(301, 'It works!'));
    }
}

class Controller implements MiddlewareInterface
{
    use MiddlewareTrait;

    public $database;
    /** @var App $_app */
    private $_app;

    public function __construct(App $app, Database $database)
    {

        $this->database = $database;
        $this->_app = $app;
    }

    protected function handleRequest(callable $next)
    {

        $data = [];
        $data[] = $this->database->getData();

        if ($this->_app->has(Model::class))
            $data[] = $this->_app->get(Model::class)->getData();

        return $next($this->request, $this->response
            ->withBody(new StringStream(
                $this->_app->get(Renderer::class)->render($data)
            ))
        );
    }
}


class AppTest extends \PHPUnit_Framework_TestCase
{

    public function testMiddleware()
    {

        $app = new App(['path' => __DIR__]);
        $app->append(function($request, ResponseInterface $response, $next) {

            return $next($request, $response->withHeader('test', 'test value'));
        });
        $response = $app->run();

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('It works!', $response->getReasonPhrase());
        $this->assertEquals('{Data from Database}, {Data from Model}', (string)$response->getBody());
        $this->assertEquals('test value', $response->getHeaderLine('test'));
    }
}