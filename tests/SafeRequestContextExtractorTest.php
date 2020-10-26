<?php

namespace Garbetjie\Http\RequestLogging\Tests;

use Garbetjie\Http\RequestLogging\SafeRequestContextExtractor;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class SafeRequestContextExtractorTest extends TestCase
{
    use CreatesRequests;

    /**
     * @dataProvider requestProvider
     * @param $request
     *
     * @throws \ReflectionException
     */
    public function testHeadersAreMasked($request)
    {
        $extractor = new SafeRequestContextExtractor();
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
            // Guzzle/PSR requests.
            'guzzle/psr incoming server request' => [$this->createPsrServerRequest()],
            'guzzle/psr outgoing request' => [$this->createPsrRequest()],

            // Laravel middleware request.
            'laravel incoming server request' => [$this->createLaravelRequest()],
        ];
    }
}
