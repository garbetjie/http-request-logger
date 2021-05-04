<?php

namespace Garbetjie\RequestLogging\Http\Tests\Middleware;

use Garbetjie\RequestLogging\Http\Logger;
use Garbetjie\RequestLogging\Http\Middleware\OutgoingRequestLoggingMiddleware;
use Garbetjie\RequestLogging\Http\Tests\ArrayMonologHandler;
use Garbetjie\RequestLogging\Http\Tests\CreatesRequests;
use Garbetjie\RequestLogging\Http\Tests\CreatesResponses;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\TestCase;
use function array_column;

class OutgoingRequestLoggingMiddlewareTest extends TestCase
{
    use CreatesRequests, CreatesResponses;

    protected $middleware;
    protected $handler;

    protected function setUp(): void
    {
        $this->handler = new ArrayMonologHandler();

        $this->middleware = new OutgoingRequestLoggingMiddleware(
            new Logger(
                new \Monolog\Logger('name', [$this->handler]),
                'debug'
            )
        );
    }

    public function testMiddlewareIsCallable()
    {
        $this->assertIsCallable($this->middleware);
    }

    public function testGuzzleRequestHandling()
    {
        $handler = new MockHandler([$this->createPsrResponse()]);
        $stack = HandlerStack::create($handler);
        $stack->push($this->middleware);
        $client = new Client(['handler' => $stack]);

        $client->send($this->createPsrRequest());

        $this->assertCount(2, $this->handler->logs());
        $this->assertEquals(['http request sent', 'http response received'], array_column($this->handler->logs(), 'message'));
    }
}
