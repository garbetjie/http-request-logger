<?php

namespace Garbetjie\Http\RequestLogging\Tests;

use GuzzleHttp\Psr7\Response as PsrResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

trait CreatesResponses
{
    protected function createPsrResponse(): PsrResponse
    {
        return new PsrResponse(
            200,
            [
                'Content-Type' => 'custom',
                'Set-Cookie' => 'key=value',
                'Authorization' => 'Bearer 123',
            ],
            'body'
        );
    }

    protected function createSymfonyResponse(): SymfonyResponse
    {
        return new SymfonyResponse(
            'body',
            200,
            [
                'Content-Type' => 'custom',
                'Set-Cookie' => 'key=value',
                'Authorization' => 'Bearer 456',
            ]
        );
    }

    protected function createStringResponse(): string
    {
        return 'body';
    }
}
