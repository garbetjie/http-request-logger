<?php

namespace Garbetjie\Http\RequestLogging\Tests;

use Garbetjie\Http\RequestLogging\LoggedRequest;
use Garbetjie\Http\RequestLogging\Logger;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use function array_column;
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

    protected function setUp(): void
    {
        $this->handler = new ArrayMonologHandler();
        $this->logger = new Logger(new Monolog('test', [$this->handler]), 'debug');
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
            LoggedRequest::class,
            $this->logger->request($this->createSymfonyRequest(), Logger::DIRECTION_IN)
        );
    }

    public function testReturnValueWhenLoggingResponse()
    {
        $request = $this->createSymfonyRequest();
        $logged = $this->logger->request($request, Logger::DIRECTION_IN);

        $this->assertNull(
            $this->logger->response($request, $this->createSymfonyResponse(), $logged)
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

        $request = $this->createSymfonyRequest();
        $response = $this->createSymfonyResponse();

        $this->logger->response(
            $request,
            $response,
            $this->logger->request($request, $this->logger::DIRECTION_IN)
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
            $request,
            $response,
            $this->logger->request($request, $direction)
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
            function (string $what, string $direction) use ($message) {
                return "{$what}:{$direction}:{$message}";
            }
        );

        $request = $this->createSymfonyRequest();
        $response = $this->createSymfonyResponse();

        $this->logger->response(
            $request,
            $response,
            $this->logger->request($request, $direction)
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
     * @param callable $message
     * @param array $expectedMessages
     */
    public function testEnabledCanBeCustomised($requestToggle, $responseToggle, string $direction, callable $message, array $expectedMessages)
    {
        $request = $this->createSymfonyRequest();
        $response = $this->createSymfonyResponse();

        $this->logger->enabled($requestToggle, $responseToggle);
        $this->logger->message($message);

        $this->logger->response(
            $request,
            $response,
            $this->logger->request($request, $direction)
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
            $request,
            $response,
            $this->logger->request($request, $direction)
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
            function ($what, $direction) {
                $this->assertContains($what, ['request', 'response']);
                $this->assertContains($direction, [$this->logger::DIRECTION_IN, $this->logger::DIRECTION_OUT]);
            }
        );

        $request = $this->createSymfonyRequest();
        $response = $this->createSymfonyResponse();

        $this->logger->response(
            $request,
            $response,
            $this->logger->request($request, $direction)
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
            function () {
                $this->assertRequestCallableArguments(...func_get_args());
            },
            function () {
                $this->assertResponseCallableArguments(...func_get_args());
            }
        );

        $this->logger->response(
            $request,
            $response,
            $this->logger->request($request, $direction)
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
            function() {
                $this->assertRequestCallableArguments(...func_get_args());

                return [];
            },
            function() {
                $this->assertResponseCallableArguments(...func_get_args());

                return [];
            }
        );

        $this->logger->response(
            $request,
            $response,
            $this->logger->request($request, $direction)
        );
    }

    protected function assertRequestCallableArguments($request, $direction)
    {
        $this->assertThat(
            $request,
            $this->logicalOr(
                $this->isType('string'),
                $this->isInstanceOf(RequestInterface::class),
                $this->isInstanceOf(SymfonyRequest::class),
            ),
        );

        $this->assertContains($direction, [$this->logger::DIRECTION_IN, $this->logger::DIRECTION_OUT]);
    }

    protected function assertResponseCallableArguments($response, $request, $direction)
    {
        $this->assertThat(
            $response,
            $this->logicalOr(
                $this->isType('string'),
                $this->isInstanceOf(ResponseInterface::class),
                $this->isInstanceOf(SymfonyResponse::class),
            )
        );

        $this->assertThat(
            $request,
            $this->logicalOr(
                $this->isType('string'),
                $this->isInstanceOf(RequestInterface::class),
                $this->isInstanceOf(SymfonyRequest::class),
            ),
        );

        $this->assertContains($direction, [$this->logger::DIRECTION_IN, $this->logger::DIRECTION_OUT]);
    }
}
