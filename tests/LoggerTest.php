<?php

namespace Garbetjie\Http\RequestLogging\Tests;

use Garbetjie\Http\RequestLogging\RequestLogEntry;
use Garbetjie\Http\RequestLogging\Logger;
use Garbetjie\Http\RequestLogging\ResponseLogEntry;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response as LaravelResponse;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use function array_column;
use function array_pad;
use function base64_encode;
use function func_get_args;
use function is_string;
use function random_bytes;
use function spl_object_hash;

class LoggerTest extends TestCase
{
    use CreatesRequests, CreatesResponses;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ArrayMonologHandler
     */
    protected $handler;

    // TODO Ensure that the startedAt SplObjectStorage object is empty after logging a request/response.
    // TODO Ensure that message(), context() and enabled() receive RequestLogEntry|ResponseLogEntry.

    protected function setUp(): void
    {
        $this->handler = new ArrayMonologHandler();
        $this->logger = new Logger(new Monolog('test', [$this->handler]), 'debug');
    }

    /**
     * @dataProvider \Garbetjie\Http\RequestLogging\Tests\DataProviders\LoggerTestDataProviders::requestResponseAndDirection()
     *
     * @param $request
     * @param $response
     * @param string $direction
     */
    public function testCorrectArgumentsArePassedToMessageCallable($request, $response, string $direction)
    {
        $called = 0;

        $this->logger->message(
            function (...$args) use (&$called) {
                $called++;

                $this->assertCount(1, $args);
                $this->assertInstanceOf(RequestLogEntry::class, $args[0]);
            },
            function (...$args) use (&$called) {
                $called++;

                $this->assertCount(1, $args);
                $this->assertInstanceOf(ResponseLogEntry::class, $args[0]);
            }
        );

        $this->logger->response(
            $this->logger->request($request, $direction),
            $response
        );

        $this->assertEquals(2, $called);
    }

    /**
     * @dataProvider \Garbetjie\Http\RequestLogging\Tests\DataProviders\LoggerTestDataProviders::requestResponseAndDirection()
     *
     * @param $request
     * @param $response
     * @param string $direction
     */
    public function testCorrectArgumentsArePassedToContextCallables($request, $response, string $direction)
    {
        $called = 0;

        $this->logger->context(
            function (...$args) use (&$called) {
                $called++;

                $this->assertCount(1, $args);
                $this->assertInstanceOf(RequestLogEntry::class, $args[0]);
            },
            function (...$args) use (&$called) {
                $called++;

                $this->assertCount(1, $args);
                $this->assertInstanceOf(ResponseLogEntry::class, $args[0]);
            }
        );

        $this->logger->response(
            $this->logger->request($request, $direction),
            $response
        );

        $this->assertEquals(2, $called);
    }

    /**
     * @dataProvider \Garbetjie\Http\RequestLogging\Tests\DataProviders\LoggerTestDataProviders::requestResponseAndDirection()
     *
     * @param $request
     * @param $response
     * @param string $direction
     */
    public function testCorrectArgumentsArePassedToEnabledCallables($request, $response, string $direction)
    {
        $called = 0;

        $this->logger->enabled(
            function (...$args) use (&$called) {
                $called++;

                $this->assertCount(1, $args);
                $this->assertInstanceOf(RequestLogEntry::class, $args[0]);

                return true;
            },
            function (...$args) use (&$called) {
                $called++;

                $this->assertCount(1, $args);
                $this->assertInstanceOf(ResponseLogEntry::class, $args[0]);

                return true;
            }
        );

        $this->logger->response(
            $this->logger->request($request, $direction),
            $response
        );

        $this->assertEquals(2, $called);
    }

    /**
     * @dataProvider \Garbetjie\Http\RequestLogging\Tests\DataProviders\LoggerTestDataProviders::returnValueWhenCustomising()
     *
     * @param string $method
     * @param array $args
     */
    public function testReturnValueWhenCustomising(string $method, array $args)
    {
        $this->assertInstanceOf(Logger::class, $this->logger->{$method}(...$args));
    }

    public function testReturnValueWhenLoggingRequest()
    {
        $this->assertInstanceOf(
            RequestLogEntry::class,
            $this->logger->request($this->createLaravelRequest(), Logger::DIRECTION_IN)
        );
    }

    public function testReturnValueWhenLoggingResponse()
    {
        $request = $this->createLaravelRequest();
        $logged = $this->logger->request($request, Logger::DIRECTION_IN);

        $this->assertNull(
            $this->logger->response($logged, $this->createLaravelResponse())
        );
    }

    public function testIdCanBeCustomised()
    {
        $id = base64_encode(random_bytes(4));

        $this->logger->id(
            function() use ($id) {
                return $id;
            }
        );

        $this->logger->response(
            $this->logger->request($this->createLaravelRequest(), $this->logger::DIRECTION_IN),
            $this->createLaravelResponse(),
        );

        $this->assertEquals($id, $this->handler->logs(0)['context']['id']);
        $this->assertEquals($id, $this->handler->logs(1)['context']['id']);
    }

    /**
     * @dataProvider \Garbetjie\Http\RequestLogging\Tests\DataProviders\LoggerTestDataProviders::contextCanBeCustomised()
     *
     * @param $request
     * @param $response
     * @param string $direction
     * @param callable $requestContext
     * @param callable $responseContext
     * @param array $expectedRequestContext
     * @param array $expectedResponseContext
     */
    public function testContextCanBeCustomised(
        $request,
        $response,
        string $direction,
        callable $requestContext,
        callable $responseContext,
        array $expectedRequestContext,
        array $expectedResponseContext
    ) {
        $this->logger->context($requestContext, $responseContext);

        $this->logger->response(
            $this->logger->request($request, $direction),
            $response,
        );

        foreach ($expectedRequestContext as $key => $value) {
            $this->assertArrayHasKey('context', $this->handler->logs(0));
            $this->assertArrayHasKey($key, $this->handler->logs(0)['context']);
            $this->assertEquals($value, $this->handler->logs(0)['context'][$key]);
        }

        foreach ($expectedResponseContext as $key => $value) {
            $this->assertArrayHasKey('context', $this->handler->logs(1));
            $this->assertArrayHasKey($key, $this->handler->logs(1)['context']);
            $this->assertEquals($value, $this->handler->logs(1)['context'][$key]);
        }
    }

    /**
     * @dataProvider \Garbetjie\Http\RequestLogging\Tests\DataProviders\LoggerTestDataProviders::messageCanBeCustomised()
     *
     * @param string $direction
     * @param string $expectedRequestPrefix
     * @param string $expectedResponsePrefix
     *
     * @throws \Exception
     */
    public function testMessageCanBeCustomised(string $direction, string $expectedRequestPrefix, string $expectedResponsePrefix)
    {
        $message = base64_encode(random_bytes(16));

        $this->logger->message(
            function (RequestLogEntry $logEntry) use ($message) {
                return "request:{$logEntry->direction()}:{$message}";
            },
            function (ResponseLogEntry $logEntry) use ($message) {
                return "response:{$logEntry->direction()}:{$message}";
            },
        );

        $this->logger->response(
            $this->logger->request($this->createLaravelRequest(), $direction),
            $this->createLaravelResponse(),
        );

        $this->assertArrayHasKey('message', $this->handler->logs(0));
        $this->assertEquals("{$expectedRequestPrefix}:{$message}", $this->handler->logs(0)['message']);

        $this->assertArrayHasKey('message', $this->handler->logs(1));
        $this->assertEquals("{$expectedResponsePrefix}:{$message}", $this->handler->logs(1)['message']);
    }

    /**
     * @dataProvider \Garbetjie\Http\RequestLogging\Tests\DataProviders\LoggerTestDataProviders::enabledCanBeCustomised()
     *
     * @param callable|bool $requestToggle
     * @param callable|bool $responseToggle
     * @param string $direction
     * @param callable $reqMessage
     * @param callable $resMessage
     * @param array $expectedMessages
     */
    public function testEnabledCanBeCustomised(
        $requestToggle,
        $responseToggle,
        string $direction,
        callable $reqMessage,
        callable $resMessage,
        array $expectedMessages
    ) {
        $request = $this->createLaravelRequest();
        $response = $this->createLaravelResponse();

        $this->logger->enabled($requestToggle, $responseToggle);
        $this->logger->message($reqMessage, $resMessage);

        $this->logger->response(
            $this->logger->request($request, $direction),
            $response,
        );

        $this->assertCount(count($expectedMessages), $this->handler->logs());
        $this->assertEquals($expectedMessages, array_column($this->handler->logs(), 'message'));
    }

    /**
     * @dataProvider \Garbetjie\Http\RequestLogging\Tests\DataProviders\LoggerTestDataProviders::requestResponseAndDirection()
     *
     * @param $request
     * @param $response
     * @param string $direction
     */
    public function testRequestsAndResponsesAreLogged($request, $response, string $direction)
    {
        $this->logger->enabled(true, true);

        $this->logger->response(
            $this->logger->request($request, $direction),
            $response,
        );

        $this->assertCount(2, $this->handler->logs());
    }

    /**
     * @dataProvider \Garbetjie\Http\RequestLogging\Tests\DataProviders\LoggerTestDataProviders::messageCallableArguments()
     *
     * @param string $direction
     */
    public function testMessageCallableArguments(string $direction)
    {
        $this->logger->message(
            function(...$args) {
                $this->assertCount(1, $args);
                $this->assertInstanceOf(RequestLogEntry::class, $args[0]);

                return '';
            },
            function(...$args) {
                $this->assertCount(1, $args);
                $this->assertInstanceOf(ResponseLogEntry::class, $args[0]);

                return '';
            },
        );

        $request = $this->createLaravelRequest();
        $response = $this->createLaravelResponse();

        $this->logger->response(
            $this->logger->request($request, $direction),
            $response,
        );
    }

    /**
     * @dataProvider \Garbetjie\Http\RequestLogging\Tests\DataProviders\LoggerTestDataProviders::requestResponseAndDirection()
     *
     * @param $request
     * @param $response
     * @param string $direction
     */
    public function testEnabledCallableArguments($request, $response, string $direction)
    {
        $this->logger->enabled(
            function(...$args) {
                $this->assertCount(1, $args);
                $this->assertInstanceOf(RequestLogEntry::class, $args[0]);

                return [];
            },
            function(...$args) {
                $this->assertCount(1, $args);
                $this->assertInstanceOf(ResponseLogEntry::class, $args[0]);

                return [];
            }
        );

        $this->logger->response(
            $this->logger->request($request, $direction),
            $response,
        );
    }


    /**
     * @dataProvider \Garbetjie\Http\RequestLogging\Tests\DataProviders\LoggerTestDataProviders::requestResponseAndDirection()
     *
     * @param $request
     * @param $response
     * @param string $direction
     */
    public function testContextCallableArguments($request, $response, string $direction)
    {
        $this->logger->context(
            function(...$args) {
                $this->assertCount(1, $args);
                $this->assertInstanceOf(RequestLogEntry::class, $args[0]);

                return [];
            },
            function(...$args) {
                $this->assertCount(1, $args);
                $this->assertInstanceOf(ResponseLogEntry::class, $args[0]);

                return [];
            }
        );

        $this->logger->response(
            $this->logger->request($request, $direction),
            $response,
        );
    }
}
