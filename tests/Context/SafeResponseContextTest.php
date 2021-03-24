<?php

namespace Garbetjie\Http\RequestLogging\Tests\Context;

use Garbetjie\Http\RequestLogging\Context\SafeResponseContext;
use Garbetjie\Http\RequestLogging\Tests\CreatesResponses;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class SafeResponseContextTest extends TestCase
{
    use CreatesResponses;

    /**
     * @dataProvider responseProvider
     * @param $response
     */
    public function testHeadersAreMasked($response)
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

    public function responseProvider()
    {
        return [
            'psr response' => [$this->createPsrResponse()],
            'laravel response' => [$this->createLaravelResponse()],
            'string response' => [$this->createStringResponse()],
        ];
    }
}
