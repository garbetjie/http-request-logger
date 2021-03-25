<?php

namespace Garbetjie\Http\RequestLogging\Tests;

use Garbetjie\Http\RequestLogging\Logger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use function base64_encode;
use function is_string;
use function random_bytes;
use function spl_object_hash;

class LoggerTest extends TestCase
{
    use CreatesRequests, CreatesResponses;

    protected $logger;
    protected $handler;

    protected function setUp(): void
    {
        $this->handler = new ArrayMonologHandler();
        $this->logger = new Logger(new Monolog('test', [$this->handler]), 'debug');
        $this->logger->enabled(true, true);
    }

    // test we can toggle requests/responses
    // test logging takes place correctly

    public function testIdGenerationCanBeCustomised()
    {
        $random = base64_encode(random_bytes(4));

        $this->logger->id(
            function() use ($random) {
                return $random;
            }
        );

        $request = $this->createSymfonyRequest();
        $response = $this->createSymfonyResponse();

        // Log the request and response.
        $this->logger->response(
            $request,
            $response,
            $this->logger->request($request, Logger::DIRECTION_IN)
        );

        // Ensure we have the expected number of logs.
        $this->assertCount(2, $this->handler->logs());

        // Ensure the IDs match up.
        $this->assertArrayHasKey('id', $this->handler->logs(0)['context']);
        $this->assertArrayHasKey('id', $this->handler->logs(1)['context']);
        $this->assertEquals($random, $this->handler->logs(0)['context']['id']);
        $this->assertEquals($random, $this->handler->logs(1)['context']['id']);
    }

    public function testMessageCanBeCustomised()
    {
        $message = base64_encode(random_bytes(16));

        $this->logger->message(
            function(string $what, string $direction) use ($message) {
                return "{$what}:{$direction}:{$message}";
            }
        );

        $request = $this->createSymfonyRequest();
        $response = $this->createSymfonyResponse();

        $this->logger->response(
            $request,
            $response,
            $this->logger->request($request, Logger::DIRECTION_IN)
        );

        $this->assertCount(2, $this->handler->logs());
        $this->assertArrayHasKey('message', $this->handler->logs(0));
        $this->assertArrayHasKey('message', $this->handler->logs(1));
        $this->assertEquals("request:in:{$message}", $this->handler->logs(0)['message']);
        $this->assertEquals("response:out:{$message}", $this->handler->logs(1)['message']);
    }

    /**
     * @dataProvider \Garbetjie\Http\RequestLogging\Tests\DataProviders\LoggerTestDataProviders::extractorsCanBeCustomised()
     *
     * @param $request
     * @param $response
     * @param $requestExtractor
     * @param $responseExtractor
     * @param string $direction
     * @param $expectedRequestContext
     * @param $expectedResponseContext
     */
    public function testContextExtractorsCanBeCustomised(
        $request,
        $response,
        $requestExtractor,
        $responseExtractor,
        string $direction,
        $expectedRequestContext,
        $expectedResponseContext
    ) {
        $this->logger->context($requestExtractor, $responseExtractor);

        $this->logger->response(
            $request,
            $response,
            $this->logger->request($request, $direction)
        );

        $this->assertCount(2, $this->handler->logs());

        foreach ($expectedRequestContext as $key => $value) {
            $this->assertArrayHasKey($key, $this->handler->logs(0)['context']);
            $this->assertEquals($value, $this->handler->logs(0)['context'][$key]);
        }

        foreach ($expectedResponseContext as $key => $value) {
            $this->assertArrayHasKey($key, $this->handler->logs(1)['context']);
            $this->assertEquals($value, $this->handler->logs(1)['context'][$key]);
        }
    }

    /**
     * @dataProvider \Garbetjie\Http\RequestLogging\Tests\DataProviders\LoggerTestDataProviders::requestsAndResponsesCanBeToggled()
     *
     * @param callable|bool $requestToggle
     * @param callable|bool $responseToggle
     * @param string $direction
     * @param int $expectedCount
     */
    public function testRequestsAndResponsesCanBeToggled($requestToggle, $responseToggle, string $direction, int $expectedCount)
    {
        $request = $this->createSymfonyRequest();
        $response = $this->createSymfonyResponse();

        $this->logger->enabled($requestToggle, $responseToggle);

        $this->logger->response(
            $request,
            $response,
            $this->logger->request($request, $direction)
        );

        $this->assertCount($expectedCount, $this->handler->logs());
    }

    /**
     * @dataProvider \Garbetjie\Http\RequestLogging\Tests\DataProviders\LoggerTestDataProviders::requestsAndResponsesAreLoggedCorrectly()
     *
     * @param $request
     * @param $response
     * @param string $direction
     */
    public function testRequestsAndResponsesAreLoggedCorrectly($request, $response, string $direction)
    {
        $this->logger->enabled(true, true);

        $this->logger->response(
            $request,
            $response,
            $this->logger->request($request, $direction)
        );

        $this->assertCount(2, $this->handler->logs());
    }
}
