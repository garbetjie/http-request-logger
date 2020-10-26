<?php

namespace Garbetjie\Http\RequestLogging\Tests;

use Garbetjie\Http\RequestLogging\SafeRequestContextExtractor;
use Garbetjie\Http\RequestLogging\SafeResponseContextExtractor;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class SafeResponseContextExtractorTest extends TestCase
{
    use CreatesResponses;

    /**
     * @dataProvider responseProvider
     * @param $response
     * @throws \ReflectionException
     */
    public function testHeadersAreMasked($response)
    {
        $extractor = new SafeResponseContextExtractor();
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
        ];
    }
}
