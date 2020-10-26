<?php

namespace Garbetjie\Http\RequestLogging\Tests;

use Garbetjie\Http\RequestLogging\ResponseContextExtractor;
use PHPUnit\Framework\TestCase;
use function base64_encode;

class ResponseContextExtractorTest extends TestCase
{
    use CreatesResponses;

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
            'guzzle/psr response' => [$this->createPsrResponse()],
            'laravel response' => [$this->createLaravelResponse()],
        ];
    }
}
