<?php

namespace Garbetjie\Http\RequestLogging\Tests;

trait CreatesResponses
{
    protected function createPsrResponse()
    {
        return new \GuzzleHttp\Psr7\Response(
            200,
            [
                'Content-Type' => 'custom',
                'Set-Cookie' => 'key=value',
            ],
            'body'
        );
    }

    protected function createLaravelResponse()
    {
        return new \Illuminate\Http\Response(
            'body',
            200,
            [
                'Content-Type' => 'custom',
                'Set-Cookie' => 'key=value',
            ]
        );
    }
}
