<?php

namespace Tale\Test;

use Psr\Http\Message\ResponseInterface;
use Tale\App;
use Tale\App\PluginInterface;
use Tale\App\PluginTrait;
use Tale\Stream\StringStream;

class Model implements PluginInterface
{
    use PluginTrait;

    public function getData()
    {

        return '{Data from Model}';
    }
}

class Database implements PluginInterface
{
    use PluginTrait;

    public function getData()
    {

        return '{Data from Database}';
    }
}

class Renderer implements PluginInterface
{
    use PluginTrait;

    public function render(array $data)
    {

        return implode(', ', $data);
    }

    public function handle()
    {

        $this->setResponse(
            $this->getResponse()->withStatus(301, 'It works!')
        );

        return $this->next();
    }
}

class Controller implements PluginInterface
{
    use PluginTrait;

    public $database;

    public function __construct(Database $database)
    {

        $this->database = $database;
    }

    protected function handle()
    {

        $data = [];
        $data[] = $this->database->getData();

        if ($this->has(Model::class))
            $data[] = $this->get(Model::class)->getData();

        $this->setResponse(
            $this->getResponse()
                ->withBody(new StringStream(
                    $this->get(Renderer::class)->render($data)
                ))
        );

        return $this->next();
    }
}


class AppTest extends \PHPUnit_Framework_TestCase
{

    public function testMiddleware()
    {


        $app = new App(['path' => __DIR__]);
        $app->useMiddleware(function($request, ResponseInterface $response, $next) {

            return $next($request, $response->withHeader('test', 'test value'));
        });
        $response = $app->dispatch();

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('It works!', $response->getReasonPhrase());
        $this->assertEquals('{Data from Database}, {Data from Model}', (string)$response->getBody());
        $this->assertEquals('test value', $response->getHeaderLine('test'));
    }
}