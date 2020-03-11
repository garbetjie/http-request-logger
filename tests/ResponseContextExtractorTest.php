<?php

namespace Garbetjie\Http\RequestLogging\Tests;

use Garbetjie\Http\RequestLogging\ContextExtractorInterface;
use Garbetjie\Http\RequestLogging\ResponseContextExtractor;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use function base64_encode;

class ResponseContextExtractorTest extends TestCase
{
    public function testImplementsInterface()
    {
        $this->assertIsCallable(new ResponseContextExtractor());
    }

    /**
     * @dataProvider responseProvider
     *
     * @param $response
     */
    public function testContextExtraction($response)
    {
        $extractor = new ResponseContextExtractor();
        $context = $extractor($response);

        foreach (['status_code', 'body', 'body_length', 'headers'] as $key) {
            $this->assertArrayHasKey($key, $context);
        }

        $this->assertEquals(base64_encode('body'), $context['body']);
        $this->assertEquals(4, $context['body_length']);
        $this->assertIsArray($context['headers']);
        $this->assertArrayHasKey('content-type', $context['headers']);
        $this->assertEquals('custom', $context['headers']['content-type']);
    }

    public function responseProvider()
    {
        return [
            'guzzle/psr response' => [new Response(200, ['Content-Type' => 'custom'], 'body')],
            'laravel response' => [new \Illuminate\Http\Response('body', 200, ['Content-Type' => 'custom'])],
        ];
    }
}
