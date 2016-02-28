<?php

namespace Tale\Test;

use Psr\Http\Message\ResponseInterface;
use Tale\App;
use Tale\App\ServiceTrait;
use Tale\Stream\StringStream;

class Model implements App\ServiceInterface
{
    use ServiceTrait;

    public function getData()
    {

        return '{Data from Model}';
    }
}

class Database implements App\ServiceInterface
{
    use ServiceTrait;

    public function getData()
    {

        return '{Data from Database}';
    }
}

class Renderer implements App\ServiceInterface
{
    use ServiceTrait;

    public function render(array $data)
    {

        return implode(', ', $data);
    }

    protected function handle()
    {

        return $this->next(null, $this->getResponse()->withStatus(301, 'It works!'));
    }
}

class Controller implements App\ServiceInterface
{
    use ServiceTrait;

    public $database;
    /** @var App $_app */
    private $_app;

    public function __construct(App $app, Database $database)
    {

        $this->database = $database;
        $this->_app = $app;
    }

    protected function handle()
    {

        $data = [];
        $data[] = $this->database->getData();

        if ($this->_app->has(Model::class))
            $data[] = $this->_app->get(Model::class)->getData();

        return $this->next(null, $this->getResponse()
            ->withBody(new StringStream(
                $this->_app->get(Renderer::class)->render($data)
        )));
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
        $response = $app->dispatch();

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('It works!', $response->getReasonPhrase());
        $this->assertEquals('{Data from Database}, {Data from Model}', (string)$response->getBody());
        $this->assertEquals('test value', $response->getHeaderLine('test'));
    }
}