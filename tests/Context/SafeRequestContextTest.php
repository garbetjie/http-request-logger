<?php

namespace Garbetjie\RequestLogging\Http\Tests\Context;

use Garbetjie\RequestLogging\Http\Context\SafeRequestContext;
use Garbetjie\RequestLogging\Http\Logger;
use Garbetjie\RequestLogging\Http\RequestEntry;
use Garbetjie\RequestLogging\Http\Tests\CreatesRequests;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class SafeRequestContextTest extends TestCase
{
    use CreatesRequests;

    public function testIsCallable()
    {
        $this->assertIsCallable(new SafeRequestContext());
    }

    public function testIncomingPsrRequestIsMasked()
    {
        $this->assertHeadersAreMasked($this->createPsrServerRequest());
    }

    public function testOutgoingPsrRequestIsMasked()
    {
        $this->assertHeadersAreMasked($this->createPsrRequest());
    }

    public function testIncomingLaravelRequestIsMasked()
    {
        $this->assertHeadersAreMasked($this->createSymfonyRequest());
    }

    public function testIncomingStringRequestIsMasked()
    {
        $this->assertHeadersAreMasked($this->createStringRequest());
    }

    protected function assertHeadersAreMasked($request)
    {
        $extractor = new SafeRequestContext();
        $context = $extractor(new RequestEntry($request, 'id', Logger::DIRECTION_IN));

        $reflection = new ReflectionObject($extractor);
        $headersProp = $reflection->getProperty('headers');
        $headersProp->setAccessible(true);

        $replacementProp = $reflection->getProperty('replacement');
        $replacementProp->setAccessible(true);

        foreach ($headersProp->getValue($extractor) as $header) {
            $this->assertArrayHasKey($header, $context['headers']);
            $this->assertEquals($replacementProp->getValue($extractor), $context['headers'][$header]);
        }
    }
}
