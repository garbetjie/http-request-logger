<?php

namespace Garbetjie\Http\RequestLogging\Tests\Context;

use Garbetjie\Http\RequestLogging\Context\SafeResponseContext;
use Garbetjie\Http\RequestLogging\Tests\CreatesResponses;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class SafeResponseContextTest extends TestCase
{
    use CreatesResponses;

    public function testIsCallable()
    {
        $this->assertIsCallable(new SafeResponseContext());
    }

    public function testLaravelResponseValuesAreMasked()
    {
        $this->assertHeadersAreMasked($this->createLaravelResponse());
    }

    public function testPsrResponseValuesAreMasked()
    {
        $this->assertHeadersAreMasked($this->createPsrResponse());
    }

    public function testStringResponseValuesAreMasked()
    {
        $this->assertHeadersAreMasked($this->createStringResponse());
    }

    protected function assertHeadersAreMasked($response)
    {
        $extractor = new SafeResponseContext();
        $context = $extractor($response);

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
