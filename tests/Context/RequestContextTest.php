<?php

namespace Garbetjie\Http\RequestLogging\Tests\Context;

use Garbetjie\Http\RequestLogging\Context\RequestContext;
use Garbetjie\Http\RequestLogging\Tests\CreatesRequests;
use PHPUnit\Framework\TestCase;
use function base64_encode;
use function strlen;

class RequestContextTest extends TestCase
{
    use CreatesRequests;

    public function testIsCallable()
    {
        $this->assertIsCallable(new RequestContext());
    }

    public function testIncomingPsrRequest()
    {
        $context = (new RequestContext())->__invoke($this->createPsrServerRequest());

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'GET', 'body', ['content-type']);
        $this->assertEquals('https://example.org/path?q=1', $context['url']);
    }

    public function testOutgoingPsrRequest()
    {
        $context = (new RequestContext())->__invoke($this->createPsrRequest());

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'GET', 'body', ['content-type']);
        $this->assertEquals('https://example.org/path?q=1', $context['url']);
    }

    public function testIncomingSymfonyRequest()
    {
        $context = (new RequestContext())->__invoke($this->createSymfonyRequest());

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'GET', 'body', ['content-type']);
        $this->assertEquals('https://example.org/path?q=1', $context['url']);
    }

    public function testIncomingStringRequestWithHttps()
    {
        $context = (new RequestContext())->__invoke($this->createStringRequest());

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'GET', 'body', ['content-type']);
        $this->assertEquals('https://example.org/path?q=1', $context['url']);
    }

    public function testIncomingStringRequestWithoutHttps()
    {
        $context = (new RequestContext())->__invoke($this->createStringRequest(false));

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'GET', 'body', ['content-type']);
        $this->assertEquals('http://example.org/path?q=1', $context['url']);
    }

    public function testIncomingStringRequestWithoutCorrectEnvironmentVariables()
    {
        $request = $this->createStringRequest();
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_METHOD']);

        $context = (new RequestContext())->__invoke($request);

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, null, 'body', ['content-type']);
        $this->assertNull($context['url']);
    }

    protected function assertContextHasRequiredKeys(array $context)
    {
        foreach (['method', 'url', 'body_length', 'body', 'headers'] as $key) {
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