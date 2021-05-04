<?php

namespace Garbetjie\RequestLogging\Http\Tests\Context;

use Garbetjie\RequestLogging\Http\Context\RequestContext;
use Garbetjie\RequestLogging\Http\Logger;
use Garbetjie\RequestLogging\Http\RequestEntry;
use Garbetjie\RequestLogging\Http\Tests\CreatesRequests;
use PHPUnit\Framework\TestCase;
use function base64_encode;
use function strlen;

class RequestContextTest extends TestCase
{
    use CreatesRequests;

    /**
     * @covers \Garbetjie\RequestLogging\Http\Context\RequestContext::__construct
     */
    public function testIsCallable()
    {
        $this->assertIsCallable(new RequestContext());
    }

    public function testIncomingPsrRequest()
    {
        $context = (new RequestContext())->__invoke(
            new RequestEntry($this->createPsrServerRequest(), 'id', Logger::DIRECTION_IN)
        );

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'GET', 'body', ['content-type']);
        $this->assertEquals('https://example.org/path?q=1', $context['url']);
    }

    public function testOutgoingPsrRequest()
    {
        $context = (new RequestContext())->__invoke(
            new RequestEntry($this->createPsrRequest(), 'id', Logger::DIRECTION_IN)
        );

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'GET', 'body', ['content-type']);
        $this->assertEquals('https://example.org/path?q=1', $context['url']);
    }

    public function testIncomingLaravelRequest()
    {
        $context = (new RequestContext())->__invoke(
            new RequestEntry($this->createSymfonyRequest(), 'id', Logger::DIRECTION_IN)
        );

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'GET', 'body', ['content-type']);
        $this->assertEquals('https://example.org/path?q=1', $context['url']);
    }

    public function testIncomingStringRequestWithHttps()
    {
        $context = (new RequestContext())->__invoke(
            new RequestEntry($this->createStringRequest(), 'id', Logger::DIRECTION_IN)
        );

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'GET', 'body', ['content-type']);
        $this->assertEquals('https://example.org/path?q=1', $context['url']);
    }

    public function testIncomingStringRequestWithoutHttps()
    {
        $context = (new RequestContext())->__invoke(
            new RequestEntry($this->createStringRequest(false), 'id', Logger::DIRECTION_IN)
        );

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'GET', 'body', ['content-type']);
        $this->assertEquals('http://example.org/path?q=1', $context['url']);
    }

    public function testIncomingStringRequestWithoutCorrectEnvironmentVariables()
    {
        $request = $this->createStringRequest();
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_METHOD']);

        $context = (new RequestContext())->__invoke(
            new RequestEntry($request, 'id', Logger::DIRECTION_IN)
        );

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, null, 'body', ['content-type']);
        $this->assertNull($context['url']);
    }

    protected function assertContextHasRequiredKeys(array $context)
    {
        foreach (['id', 'method', 'url', 'body_length', 'body', 'headers'] as $key) {
            $this->assertArrayHasKey($key, $context);
        }
    }

    protected function assertContextMatches(array $context, ?string $method, ?string $body, array $headers)
    {
        $this->assertEquals($method, $context['method']);
        $this->assertEquals(base64_encode($body), $context['body']);
        $this->assertEquals(strlen($body), $context['body_length']);
        $this->assertIsArray($context['headers']);

        foreach ($headers as $header) {
            $this->assertArrayHasKey($header, $context['headers']);
        }
    }
}
