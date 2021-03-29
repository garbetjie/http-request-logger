<?php

namespace Garbetjie\Http\RequestLogging\Tests\Context;

use Garbetjie\Http\RequestLogging\Context\SafeResponseContext;
use Garbetjie\Http\RequestLogging\Logger;
use Garbetjie\Http\RequestLogging\RequestLogEntry;
use Garbetjie\Http\RequestLogging\ResponseLogEntry;
use Garbetjie\Http\RequestLogging\Tests\CreatesRequests;
use Garbetjie\Http\RequestLogging\Tests\CreatesResponses;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class SafeResponseContextTest extends TestCase
{
    use CreatesRequests, CreatesResponses;

    public function testIsCallable()
    {
        $this->assertIsCallable(new SafeResponseContext());
    }

    public function testLaravelResponseValuesAreMasked()
    {
        $this->assertHeadersAreMasked(
            $this->createLaravelRequest(),
            $this->createLaravelResponse()
        );
    }

    public function testPsrResponseValuesAreMasked()
    {
        $this->assertHeadersAreMasked(
            $this->createPsrRequest(),
            $this->createPsrResponse()
        );
    }

    public function testStringResponseValuesAreMasked()
    {
        $this->assertHeadersAreMasked(
            $this->createStringRequest(),
            $this->createStringResponse()
        );
    }

    protected function assertHeadersAreMasked($request, $response)
    {
        $extractor = new SafeResponseContext();
        $context = $extractor(
            new ResponseLogEntry(
                new RequestLogEntry($request, 'id', Logger::DIRECTION_IN),
                $response,
                1
            )
        );

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
