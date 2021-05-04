<?php

namespace Garbetjie\RequestLogging\Http\Tests\Context;

use Garbetjie\RequestLogging\Http\Context\SafeResponseContext;
use Garbetjie\RequestLogging\Http\Logger;
use Garbetjie\RequestLogging\Http\RequestEntry;
use Garbetjie\RequestLogging\Http\ResponseEntry;
use Garbetjie\RequestLogging\Http\Tests\CreatesRequests;
use Garbetjie\RequestLogging\Http\Tests\CreatesResponses;
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
            $this->createSymfonyRequest(),
            $this->createSymfonyResponse()
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
            new ResponseEntry(
                new RequestEntry($request, 'id', Logger::DIRECTION_IN),
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
