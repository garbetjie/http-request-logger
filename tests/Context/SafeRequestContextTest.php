<?php

namespace Garbetjie\Http\RequestLogging\Tests\Context;

use Garbetjie\Http\RequestLogging\Context\SafeRequestContext;
use Garbetjie\Http\RequestLogging\Tests\CreatesRequests;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class SafeRequestContextTest extends TestCase
{
    use CreatesRequests;

    /**
     * @dataProvider requestProvider
     * @param $request
     */
    public function testHeadersAreMasked($request)
    {
        $extractor = new SafeRequestContext();
        $context = $extractor($request);

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

    public function requestProvider()
    {
        return [
            'incoming guzzle/psr server request' => [$this->createPsrServerRequest()],
            'outgoing guzzle/psr request' => [$this->createPsrRequest()],
            'incoming laravel server request' => [$this->createLaravelRequest()],
            'incoming string request' => [$this->createStringRequest()],
        ];
    }
}
