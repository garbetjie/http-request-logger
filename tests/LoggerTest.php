<?php

namespace Garbetjie\RequestLogging\Http\Tests;

use Garbetjie\RequestLogging\Http\Logger;
use Garbetjie\RequestLogging\Http\RequestEntry;
use Garbetjie\RequestLogging\Http\ResponseEntry;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SplObjectStorage;
use function array_column;
use function base64_encode;
use function random_bytes;
use function usleep;

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
        $this->logger = new Logger(new Monolog('test', [$this->handler]));
    }

    /**
     * @dataProvider \Garbetjie\RequestLogging\Http\Tests\DataProviders\LoggerTestDataProviders::logLevelIsRespected()
     *
     * @param int|string $level
     * @param string $name
     */
    public function testLogLevelIsRespected($level, string $name)
    {
        $logger = new Logger(new Monolog('test', [$this->handler]), $level);

        $logger->response(
            $logger->request($this->createPsrRequest(), Logger::DIRECTION_IN),
            $this->createPsrResponse()
        );

        $this->assertEquals(Monolog::toMonologLevel($level), $this->handler->logs(0)['level']);
        $this->assertEquals($name, $this->handler->logs(0)['level_name']);
        $this->assertEquals(Monolog::toMonologLevel($level), $this->handler->logs(1)['level']);
        $this->assertEquals($name, $this->handler->logs(1)['level_name']);
    }

    public function testDurationIsCalculatedCorrectly()
    {
        $request = $this->createPsrRequest();
        $entry = $this->logger->request($request, Logger::DIRECTION_IN);

        usleep(500000);
        $this->logger->response($entry, $this->createPsrResponse());

        $this->assertThat(
            $this->handler->logs(1)['context']['duration'],
            $this->logicalAnd(
                $this->greaterThanOrEqual(0.5),
                $this->lessThanOrEqual(0.6)
            )
        );
    }

    /**
     * @dataProvider \Garbetjie\RequestLogging\Http\Tests\DataProviders\LoggerTestDataProviders::requestResponseAndDirection()
     *
     * @param $requestsEnabled
     * @param $responsesEnabled
     * @throws \ReflectionException
     */
    public function testStartedAtTrackingIsAlwaysEmptied($requestsEnabled, $responsesEnabled)
    {
        $this->logger->enabled($requestsEnabled, $responsesEnabled);

        $prop = new ReflectionProperty($this->logger, 'startedAt');
        $prop->setAccessible(true);

        $this->assertInstanceOf(SplObjectStorage::class, $prop->getValue($this->logger));
        $this->assertCount(0, $prop->getValue($this->logger));

        $this->logger->response(
            $this->logger->request($this->createPsrRequest(), Logger::DIRECTION_IN),
            $this->createPsrResponse(),
        );

        $this->assertCount(0, $prop->getValue($this->logger));
    }

    /**
     * @dataProvider \Garbetjie\RequestLogging\Http\Tests\DataProviders\LoggerTestDataProviders::requestResponseAndDirection()
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
                $this->assertInstanceOf(RequestEntry::class, $args[0]);
            },
            function (...$args) use (&$called) {
                $called++;

                $this->assertCount(1, $args);
                $this->assertInstanceOf(ResponseEntry::class, $args[0]);
            }
        );

        $this->logger->response(
            $this->logger->request($request, $direction),
            $response
        );

        $this->assertEquals(2, $called);
    }

    /**
     * @dataProvider \Garbetjie\RequestLogging\Http\Tests\DataProviders\LoggerTestDataProviders::requestResponseAndDirection()
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
                $this->assertInstanceOf(RequestEntry::class, $args[0]);
            },
            function (...$args) use (&$called) {
                $called++;

                $this->assertCount(1, $args);
                $this->assertInstanceOf(ResponseEntry::class, $args[0]);
            }
        );

        $this->logger->response(
            $this->logger->request($request, $direction),
            $response
        );

        $this->assertEquals(2, $called);
    }

    /**
     * @dataProvider \Garbetjie\RequestLogging\Http\Tests\DataProviders\LoggerTestDataProviders::requestResponseAndDirection()
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
                $this->assertInstanceOf(RequestEntry::class, $args[0]);

                return true;
            },
            function (...$args) use (&$called) {
                $called++;

                $this->assertCount(1, $args);
                $this->assertInstanceOf(ResponseEntry::class, $args[0]);

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
     * @dataProvider \Garbetjie\RequestLogging\Http\Tests\DataProviders\LoggerTestDataProviders::returnValueWhenCustomising()
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
            RequestEntry::class,
            $this->logger->request($this->createSymfonyRequest(), Logger::DIRECTION_IN)
        );
    }

    public function testReturnValueWhenLoggingResponse()
    {
        $request = $this->createSymfonyRequest();
        $logged = $this->logger->request($request, Logger::DIRECTION_IN);

        $this->assertNull(
            $this->logger->response($logged, $this->createSymfonyResponse())
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
            $this->logger->request($this->createSymfonyRequest(), $this->logger::DIRECTION_IN),
            $this->createSymfonyResponse(),
        );

        $this->assertEquals($id, $this->handler->logs(0)['context']['id']);
        $this->assertEquals($id, $this->handler->logs(1)['context']['id']);
    }

    /**
     * @dataProvider \Garbetjie\RequestLogging\Http\Tests\DataProviders\LoggerTestDataProviders::contextCanBeCustomised()
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
     * @dataProvider \Garbetjie\RequestLogging\Http\Tests\DataProviders\LoggerTestDataProviders::messageCanBeCustomised()
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
            function (RequestEntry $logEntry) use ($message) {
                return "request:{$logEntry->direction()}:{$message}";
            },
            function (ResponseEntry $logEntry) use ($message) {
                return "response:{$logEntry->direction()}:{$message}";
            },
        );

        $this->logger->response(
            $this->logger->request($this->createSymfonyRequest(), $direction),
            $this->createSymfonyResponse(),
        );

        $this->assertArrayHasKey('message', $this->handler->logs(0));
        $this->assertEquals("{$expectedRequestPrefix}:{$message}", $this->handler->logs(0)['message']);

        $this->assertArrayHasKey('message', $this->handler->logs(1));
        $this->assertEquals("{$expectedResponsePrefix}:{$message}", $this->handler->logs(1)['message']);
    }

    /**
     * @dataProvider \Garbetjie\RequestLogging\Http\Tests\DataProviders\LoggerTestDataProviders::enabledCanBeCustomised()
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
        $request = $this->createSymfonyRequest();
        $response = $this->createSymfonyResponse();

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
     * @dataProvider \Garbetjie\RequestLogging\Http\Tests\DataProviders\LoggerTestDataProviders::requestResponseAndDirection()
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
     * @dataProvider \Garbetjie\RequestLogging\Http\Tests\DataProviders\LoggerTestDataProviders::messageCallableArguments()
     *
     * @param string $direction
     */
    public function testMessageCallableArguments(string $direction)
    {
        $this->logger->message(
            function(...$args) {
                $this->assertCount(1, $args);
                $this->assertInstanceOf(RequestEntry::class, $args[0]);

                return '';
            },
            function(...$args) {
                $this->assertCount(1, $args);
                $this->assertInstanceOf(ResponseEntry::class, $args[0]);

                return '';
            },
        );

        $request = $this->createSymfonyRequest();
        $response = $this->createSymfonyResponse();

        $this->logger->response(
            $this->logger->request($request, $direction),
            $response,
        );
    }

    /**
     * @dataProvider \Garbetjie\RequestLogging\Http\Tests\DataProviders\LoggerTestDataProviders::requestResponseAndDirection()
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
                $this->assertInstanceOf(RequestEntry::class, $args[0]);

                return [];
            },
            function(...$args) {
                $this->assertCount(1, $args);
                $this->assertInstanceOf(ResponseEntry::class, $args[0]);

                return [];
            }
        );

        $this->logger->response(
            $this->logger->request($request, $direction),
            $response,
        );
    }


    /**
     * @dataProvider \Garbetjie\RequestLogging\Http\Tests\DataProviders\LoggerTestDataProviders::requestResponseAndDirection()
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
                $this->assertInstanceOf(RequestEntry::class, $args[0]);

                return [];
            },
            function(...$args) {
                $this->assertCount(1, $args);
                $this->assertInstanceOf(ResponseEntry::class, $args[0]);

                return [];
            }
        );

        $this->logger->response(
            $this->logger->request($request, $direction),
            $response,
        );
    }
}
