<?php

namespace Garbetjie\Http\RequestLogging\Tests\Middleware;

use Garbetjie\Http\RequestLogging\Logger;
use Garbetjie\Http\RequestLogging\Middleware\IncomingRequestLoggingMiddleware;
use Garbetjie\Http\RequestLogging\Tests\ArrayMonologHandler;
use Garbetjie\Http\RequestLogging\Tests\CreatesRequests;
use Garbetjie\Http\RequestLogging\Tests\CreatesResponses;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function array_column;
use function method_exists;

class IncomingRequestLoggingMiddlewareTest extends TestCase
{
    use CreatesRequests, CreatesResponses;

    protected $middleware;
    protected $handler;

    protected function setUp(): void
    {
        $this->handler = new ArrayMonologHandler();

        $this->middleware = new IncomingRequestLoggingMiddleware(
            new Logger(
                new \Monolog\Logger('name', [$this->handler]),
                'debug'
            )
        );
    }

    public function testMiddlewareImplementsPsrInterface()
    {
        $this->assertInstanceOf(MiddlewareInterface::class, $this->middleware);
    }

    public function testMiddlewareAdheresToLaravelMiddleware()
    {
        $this->assertTrue(method_exists($this->middleware, 'handle'));
    }

    public function testPsrCompliantRequestHandling()
    {
        $response = $this->createPsrResponse();

        $this->middleware->process(
            $this->createPsrServerRequest(),
            new class($response) implements RequestHandlerInterface {
                protected $response;

                public function __construct(ResponseInterface $response) {
                    $this->response = $response;
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->response;
                }
            }
        );

        $this->assertCount(2, $this->handler->logs());
        $this->assertEquals(['http request received', 'http response sent'], array_column($this->handler->logs(), 'message'));
    }

    public function testLaravelRequestHandling()
    {
        $this->middleware->handle(
            $this->createLaravelRequest(),
            function() {
                return $this->createLaravelResponse();
            }
        );

        $this->assertCount(2, $this->handler->logs());
        $this->assertEquals(['http request received', 'http response sent'], array_column($this->handler->logs(), 'message'));
    }
}
