<?php

namespace Garbetjie\Http\RequestLogging\Tests\Context;

use Garbetjie\Http\RequestLogging\Context\ResponseContext;
use Garbetjie\Http\RequestLogging\Logger;
use Garbetjie\Http\RequestLogging\RequestEntry;
use Garbetjie\Http\RequestLogging\ResponseEntry;
use Garbetjie\Http\RequestLogging\Tests\CreatesRequests;
use Garbetjie\Http\RequestLogging\Tests\CreatesResponses;
use PHPUnit\Framework\TestCase;
use function base64_encode;
use function strlen;

class ResponseContextTest extends TestCase
{
    use CreatesRequests, CreatesResponses;

    protected function setUp(): void
    {
        header_remove();
    }

    public function testIsCallable()
    {
        $this->assertIsCallable(new ResponseContext());
    }

    public function testPsrResponse()
    {
        $context = (new ResponseContext())->__invoke(
            new ResponseEntry(
                new RequestEntry($this->createPsrRequest(), 'id', Logger::DIRECTION_IN),
                $this->createPsrResponse(),
                1,
            )
        );

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'body', ['content-type', 'set-cookie', 'authorization']);
    }

    public function testLaravelResponse()
    {
        $context = (new ResponseContext())->__invoke(
            new ResponseEntry(
                new RequestEntry($this->createSymfonyRequest(), 'id', Logger::DIRECTION_IN),
                $this->createSymfonyResponse(),
                1,
            )
        );

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'body', ['content-type', 'set-cookie', 'authorization']);
    }

    public function testStringResponse()
    {
        $context = (new ResponseContext())->__invoke(
            new ResponseEntry(
                new RequestEntry($this->createStringRequest(), 'id', Logger::DIRECTION_IN),
                $this->createStringResponse(),
                1
            ),
        );

        $this->assertContextHasRequiredKeys($context);
        $this->assertContextMatches($context, 'body', ['content-type', 'set-cookie', 'authorization']);
    }

    protected function assertContextHasRequiredKeys(array $context)
    {
        foreach (['id', 'duration', 'status_code', 'body', 'body_length', 'headers'] as $key) {
            $this->assertArrayHasKey($key, $context);
        }
    }

    protected function assertContextMatches(array $context, ?string $body, array $headers)
    {
        $this->assertEquals(base64_encode($body), $context['body']);
        $this->assertEquals(strlen($body), $context['body_length']);
        $this->assertIsArray($context['headers']);

        foreach ($headers as $header) {
            $this->assertArrayHasKey($header, $context['headers']);
        }
    }
}
