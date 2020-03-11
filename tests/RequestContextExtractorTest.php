<?php

namespace Garbetjie\Http\RequestLogging\Tests;

use Garbetjie\Http\RequestLogging\ContextExtractorInterface;
use Garbetjie\Http\RequestLogging\RequestContextExtractor;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use function base64_encode;

class RequestContextExtractorTest extends TestCase
{
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
            'guzzle/psr incoming server request' => [new ServerRequest('GET', 'https://example.org', ['Content-Type' => 'application/json'], 'body')],
            'guzzle/psr outgoing request' => [new Request('GET', 'https://example.org', ['Content-Type' => 'application/json'], 'body')],

            // Laravel middleware request.
            'laravel incoming server request' => [new \Illuminate\Http\Request([], [], [], [], [], ['REQUEST_METHOD' => 'GET', 'HTTP_CONTENT_TYPE' => 'application/json', 'HTTP_HOST' => 'example.org'], 'body')],
        ];
    }
}
