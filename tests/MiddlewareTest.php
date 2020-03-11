<?php

namespace Garbetjie\Http\RequestLogging\Tests;

use Closure;
use Garbetjie\Http\RequestLogging\Middleware\GuzzleMiddleware;
use Garbetjie\Http\RequestLogging\Middleware\LaravelMiddleware;
use Garbetjie\Http\RequestLogging\Middleware\Middleware;
use Garbetjie\Http\RequestLogging\Middleware\PsrMiddleware;
use Garbetjie\Http\RequestLogging\RequestContextExtractor;
use Garbetjie\Http\RequestLogging\ResponseContextExtractor;
use Garbetjie\Http\RequestLogging\SoapClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Request;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function base64_encode;
use function strlen;
use function var_dump;
use const SOAP_1_1;

class MiddlewareTest extends TestCase
{
    private $soapRequest = '<?xml version="1.0" encoding="UTF-8"?>
<soap11:Envelope
   xmlns="urn:GoogleSearch"
   xmlns:soap11="http://schemas.xmlsoap.org/soap/envelope/">
  <soap11:Body>
    <doGoogleSearch>
      <key>00000000000000000000000000000000</key>
      <q>shrdlu winograd maclisp teletype</q>
      <start>0</start>
      <maxResults>10</maxResults>
      <filter>true</filter>
      <restrict></restrict>
      <safeSearch>false</safeSearch>
      <lr></lr>
      <ie>latin1</ie>
      <oe>latin1</oe>
    </doGoogleSearch>
  </soap11:Body>
</soap11:Envelope>';

    private $soapResponse = '<?xml version="1.0" encoding="UTF-8"?>
<soap11:Envelope
  xmlns="urn:GoogleSearch"
  xmlns:google="urn:GoogleSearch"
  xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/"
  xmlns:soap11="http://schemas.xmlsoap.org/soap/envelope/">
  <soap11:Body>
    <doGoogleSearchResponse>
      <return>
        <documentFiltering>false</documentFiltering>
        <estimatedTotalResultsCount>0</estimatedTotalResultsCount>
        <directoryCategories soapenc:arrayType="google:DirectoryCategory[0]"></directoryCategories>
        <searchTime>0.194871</searchTime>
        <resultElements soapenc:arrayType="google:ResultElement[0]">
        </resultElements>
        <endIndex>0</endIndex>
        <searchTips></searchTips>
        <searchComments></searchComments>
        <startIndex>0</startIndex>
        <estimateIsExact>true</estimateIsExact>
        <searchQuery>shrdlu winograd maclisp teletype</searchQuery>
      </return>
    </doGoogleSearchResponse>
  </soap11:Body>
</soap11:Envelope>';

    /**
     * @dataProvider requestProvider
     *
     * @param ArrayMonologHandler $handler
     * @param string $requestMessage
     * @param string $responseMessage
     * @param string $responseContentType
     * @param string $requestBody
     * @param string $responseBody
     * @param Closure $executor
     */
    public function testLogMessages(ArrayMonologHandler $handler, $requestMessage, $responseMessage, $responseContentType, $requestBody, $responseBody, Closure $executor)
    {
        // Call the executor.
        $executor();

        // Ensure that there are two log messages generated.
        $this->assertCount(2, $handler->logs());

        $request = $handler->logs(0);
        $response = $handler->logs(1);

        // Ensure each log message has the correct structure.
        foreach (['message', 'context', 'level', 'level_name', 'channel', 'datetime', 'extra'] as $key) {
            $this->assertArrayHasKey($key, $request);
            $this->assertArrayHasKey($key, $response);
        }

        $this->assertEquals($requestMessage, $request['message'], 'request log message was updated');
        $this->assertEquals($responseMessage, $response['message'], 'response log message was updated');

        foreach (['id', 'method', 'url', 'body_length', 'body', 'headers'] as $key) {
            $this->assertArrayHasKey($key, $request['context']);
        }

        $this->assertEquals('POST', $request['context']['method'], 'Request method');
        $this->assertEquals('https://example.org', $request['context']['url'], 'Request URL');
        $this->assertEquals(strlen($requestBody), $request['context']['body_length'], 'Request body length');
        $this->assertEquals(base64_encode($requestBody), $request['context']['body'], 'Request body Base64 encoding');
        $this->assertIsArray($request['context']['headers'], 'Request headers');
        $this->assertArrayHasKey('content-type', $request['context']['headers'], 'Content-Type request header');
        $this->assertStringContainsString('text/xml', $request['context']['headers']['content-type'], 'Content-Type request header');

        foreach (['id', 'status_code', 'body_length', 'body', 'headers'] as $key) {
            $this->assertArrayHasKey($key, $response['context']);
        }

        $this->assertIsInt($response['context']['status_code'], 'Response status code');
        $this->assertEquals(200, $response['context']['status_code'], 'Response status code');
        $this->assertEquals(strlen($responseBody), $response['context']['body_length'], 'Response body length');
        $this->assertEquals(base64_encode($responseBody), $response['context']['body'], 'Response body Base64 encoding');
        $this->assertIsArray($response['context']['headers'], 'Response headers');
        $this->assertArrayHasKey('content-type', $response['context']['headers'], 'Content-Type response header');
        $this->assertStringContainsString($responseContentType, $response['context']['headers']['content-type'], 'Content-Type response header');

        $this->assertEquals($request['context']['id'], $response['context']['id'], 'Request ID & response ID match');
    }

    /**
     * @param Logger $logger
     * @param ArrayMonologHandler $handler
     * @param Closure $fnMiddleware
     *
     * @return Closure
     */
    private function createGuzzleExecutor($logger, $handler, $fnMiddleware = null)
    {
        return function() use ($handler, $logger, $fnMiddleware) {
            $handler->clear();

            $stack = MockHandler::createWithMiddleware([
                new Response(200, ['Content-Type' => 'application/json'], 'response body')
            ]);

            $middleware = $this->createMiddleware($logger, GuzzleMiddleware::class);

            if ($fnMiddleware) {
                $fnMiddleware($middleware);
            }

            $stack->push($middleware);

            $client = new Client(['handler' => $stack]);
            $client->request('POST', 'https://example.org', [
                RequestOptions::BODY => 'request body',
                RequestOptions::HEADERS => ['Content-Type' => 'text/xml']
            ]);
        };
    }

    /**
     * @param Logger $logger
     * @param ArrayMonologHandler $handler
     * @param Closure $fnMiddleware
     *
     * @return Closure
     */
    private function createLaravelExecutor($logger, $handler, $fnMiddleware = null)
    {
        return function() use ($logger, $handler, $fnMiddleware) {
            $handler->clear();
            $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'POST', 'HTTPS' => 'on', 'HTTP_HOST' => 'example.org', 'HTTP_CONTENT_TYPE' => 'text/xml'], 'request body');

            $middleware = $this->createMiddleware($logger, LaravelMiddleware::class);

            if ($fnMiddleware) {
                $fnMiddleware($middleware);
            }

            $middleware->handle($request, function () {
                return new \Illuminate\Http\Response('response body', 200, ['Content-Type' => 'application/json']);
            });
        };
    }

    /**
     * @param Logger $logger
     * @param ArrayMonologHandler $handler
     * @param Closure $fnMiddleware
     *
     * @return Closure
     */
    private function createPsrExecutor($logger, $handler, $fnMiddleware = null)
    {
        return function() use ($logger, $handler, $fnMiddleware) {
            $handler->clear();

            $request = new ServerRequest('POST', 'https://example.org', ['Content-Type' => 'text/xml'], 'request body');

            $middleware = $this->createMiddleware($logger, PsrMiddleware::class);
            if ($fnMiddleware) {
                $fnMiddleware($middleware);
            }

            $middleware->process($request, new class() implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return new Response(200, ['Content-Type' => 'application/json'], 'response body');
                }
            });
        };
    }

    /**
     * @param Logger $logger
     * @param ArrayMonologHandler $handler
     * @param Closure $fnMiddleware
     *
     * @return Closure
     */
    private function createSoapExecutor($logger, $handler, $fnMiddleware = null)
    {
        return function() use ($logger, $handler, $fnMiddleware) {
            $handler->clear();

            $stack = MockHandler::createWithMiddleware([
                new Response(200, ['Content-Type' => 'text/xml'], $this->soapResponse)
            ]);

            $middleware = $this->createMiddleware($logger, GuzzleMiddleware::class);

            if ($fnMiddleware) {
                $fnMiddleware($middleware);
            }

            $stack->push($middleware);

            $soapClient = new SoapClient(
                new Client(['handler' => $stack]),
                null,
                ['location' => '/', 'uri' => 'https://example.org']
            );

            $soapClient->__doRequest($this->soapRequest, 'https://example.org', 'TestCall', SOAP_1_1);
        };
    }

    /**
     * @dataProvider executorsProvider
     *
     * @param string $method
     */
    public function testSettingContextExtractorToInterface($method)
    {
        $handler = new ArrayMonologHandler();

        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $this->$method($logger, $handler, function(Middleware $middleware) {
            $middleware->withExtractors(new RequestContextExtractor(), new ResponseContextExtractor());
        })();

        $this->assertArrayHasKey('context', $handler->logs(0));
        $this->assertArrayHasKey('method', $handler->logs(0)['context']);
        $this->assertEquals('POST', $handler->logs(0)['context']['method']);

        $this->assertArrayHasKey('context', $handler->logs(1));
        $this->assertArrayHasKey('status_code', $handler->logs(1)['context']);
        $this->assertEquals(200, $handler->logs(1)['context']['status_code']);
    }

    /**
     * @dataProvider providerForDisableLoggingTests
     * @param string $method
     * @param int $expectedCount
     * @param string $testMethod
     * @param bool $requestsEnabled
     * @param bool $responsesEnabled
     */
    public function testDisablingLogging($method, $expectedCount, $testMethod, $requestsEnabled, $responsesEnabled)
    {
        $handler = new ArrayMonologHandler();

        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $this->$method($logger, $handler, function(Middleware $middleware) use ($requestsEnabled, $responsesEnabled) {
            $middleware->withDeciders(
                function() use ($requestsEnabled) {
                    return $requestsEnabled;
                },
                function() use ($responsesEnabled) {
                    return $responsesEnabled;
                }
            );
        })();

        $this->$testMethod($expectedCount, $handler->logs());
    }

    public function providerForDisableLoggingTests()
    {
        return [
            ['createGuzzleExecutor', 0, 'assertCount', false, false],
            ['createGuzzleExecutor', 1, 'assertCount', true, false],
            ['createGuzzleExecutor', 1, 'assertCount', false, true],
            ['createGuzzleExecutor', 1, 'assertNotCount', true, true],
            ['createGuzzleExecutor', 1, 'assertNotCount', false, false],
            ['createGuzzleExecutor', 2, 'assertNotCount', true, false],

            ['createSoapExecutor', 0, 'assertCount', false, false],
            ['createSoapExecutor', 1, 'assertCount', true, false],
            ['createSoapExecutor', 1, 'assertCount', false, true],
            ['createSoapExecutor', 1, 'assertNotCount', true, true],
            ['createSoapExecutor', 1, 'assertNotCount', false, false],
            ['createSoapExecutor', 2, 'assertNotCount', true, false],

            ['createLaravelExecutor', 0, 'assertCount', false, false],
            ['createLaravelExecutor', 1, 'assertCount', true, false],
            ['createLaravelExecutor', 1, 'assertCount', false, true],
            ['createLaravelExecutor', 1, 'assertNotCount', true, true],
            ['createLaravelExecutor', 1, 'assertNotCount', false, false],
            ['createLaravelExecutor', 2, 'assertNotCount', true, false],

            ['createPsrExecutor', 0, 'assertCount', false, false],
            ['createPsrExecutor', 1, 'assertCount', true, false],
            ['createPsrExecutor', 1, 'assertCount', false, true],
            ['createPsrExecutor', 1, 'assertNotCount', true, true],
            ['createPsrExecutor', 1, 'assertNotCount', false, false],
            ['createPsrExecutor', 2, 'assertNotCount', true, false],
        ];
    }

    /**
     * @dataProvider executorsProvider
     * @param string $method
     */
    public function testSettingContextExtractorToCallable($method)
    {
        $handler = new ArrayMonologHandler();

        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $this->$method($logger, $handler, function(Middleware $middleware) {
            $middleware->withExtractors(
                function ($request) {
                    return ['foo' => 'bar'];
                },
                function ($response) {
                    return ['bar' => 'baz'];
                }
            );
        })();

        $request = $handler->logs(0);
        $response = $handler->logs(1);

        $this->assertArrayHasKey('context', $request);
        $this->assertCount(2, $request['context'], 'request context');
        $this->assertArrayHasKey('foo', $request['context'], 'request context');
        $this->assertEquals('bar', $request['context']['foo'], 'request context');

        $this->assertArrayHasKey('context', $response);
        $this->assertCount(3, $response['context'], 'response context');
        $this->assertArrayHasKey('bar', $response['context'], 'response context');
        $this->assertEquals('baz', $response['context']['bar'], 'response context');
    }

    public function executorsProvider()
    {
        return [
            ['createGuzzleExecutor'],
            ['createSoapExecutor'],
            ['createLaravelExecutor'],
            ['createPsrExecutor'],
        ];
    }

    public function requestProvider()
    {
        $handler = new ArrayMonologHandler();

        $logger = new Logger('test');
        $logger->pushHandler($handler);

        // TODO Add more status codes.

        return [
            'outgoing request (guzzle)' => [
                $handler,
                'request out',
                'response in',
                'application/json',
                'request body',
                'response body',
                $this->createGuzzleExecutor($logger, $handler),
            ],

            'outgoing request (soap)' => [
                $handler,
                'request out',
                'response in',
                'text/xml',
                $this->soapRequest,
                $this->soapResponse,
                $this->createSoapExecutor($logger, $handler),
            ],

            'incoming request (laravel)' => [
                $handler,
                'request in',
                'response out',
                'application/json',
                'request body',
                'response body',
                $this->createLaravelExecutor($logger, $handler),
            ],

            'incoming request (psr)' => [
                $handler,
                'request in',
                'response out',
                'application/json',
                'request body',
                'response body',
                $this->createPsrExecutor($logger, $handler),
            ]
        ];
    }

    /**
     * @param Logger $logger
     * @param string $className
     * @return GuzzleMiddleware|LaravelMiddleware|PsrMiddleware
     */
    private function createMiddleware($logger, $className)
    {
        /* @var Middleware $middleware */

        $middleware = new $className($logger, 'debug');
        $middleware->withMessages('request in', 'response out', 'request out', 'response in');
        $middleware->withExtractors(new RequestContextExtractor(), new ResponseContextExtractor());

        return $middleware;
    }
}
