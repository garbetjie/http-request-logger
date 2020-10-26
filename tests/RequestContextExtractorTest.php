<?php

namespace Garbetjie\Http\RequestLogging\Tests;

use Garbetjie\Http\RequestLogging\RequestContextExtractor;
use PHPUnit\Framework\TestCase;
use function base64_encode;

class RequestContextExtractorTest extends TestCase
{
    use CreatesRequests;

    public function testImplementsInterface()
    {
        $this->assertIsCallable(new RequestContextExtractor());
    }

    /**
     * @dataProvider createRequests
     *
     * @param $request
     */
    public function testExtraction($request)
    {
        $extractor = new RequestContextExtractor();
        $context = $extractor($request);

        foreach (['method', 'url', 'body_length', 'body', 'headers'] as $key) {
            $this->assertArrayHasKey($key, $context);
        }

        $this->assertEquals('GET', $context['method']);
        $this->assertEquals(base64_encode('body'), $context['body']);
        $this->assertEquals(4, $context['body_length']);
        $this->assertIsArray($context['headers']);
        $this->assertArrayHasKey('content-type', $context['headers']);
    }

    public function createRequests()
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
