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
    }

    public function testOutgoingPsrRequest()
    {
        $context = (new RequestContext())->__invoke($this->createPsrRequest());

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'GET', 'body', ['content-type']);
    }

    public function testIncomingLaravelRequest()
    {
        $context = (new RequestContext())->__invoke($this->createLaravelRequest());

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'GET', 'body', ['content-type']);
    }

    public function testIncomingStringRequestWithHttps()
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['REQUEST_URI'] = '/?cow=moo';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTP_CUSTOM_HEADER'] = 'cow';

        $context = (new RequestContext())->__invoke($this->createStringRequest());

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'GET', 'body', ['host', 'custom-header']);
        $this->assertEquals('https://localhost/?cow=moo', $context['url']);
    }

    public function testIncomingStringRequestWithoutHttps()
    {
        $_SERVER['REQUEST_URI'] = '/?cow=moo';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTP_CUSTOM_HEADER'] = 'cow';
        unset($_SERVER['HTTPS']);

        $context = (new RequestContext())->__invoke($this->createStringRequest());

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'POST', 'body', ['host', 'custom-header']);
        $this->assertEquals('http://localhost/?cow=moo', $context['url']);
    }

    public function testIncomingStringRequestWithoutCorrectEnvironmentVariables()
    {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_CUSTOM_HEADER'] = 'cow';
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_METHOD']);

        $context = (new RequestContext())->__invoke($this->createStringRequest());

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, null, 'body', ['custom-header']);
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
