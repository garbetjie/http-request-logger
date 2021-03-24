<?php

namespace Garbetjie\Http\RequestLogging\Tests;

trait CreatesRequests
{
    protected function createLaravelRequest()
    {
        return new \Illuminate\Http\Request(
            [],
            [],
            [],
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_HOST' => 'example.org',
                'HTTP_COOKIE' => 'key=value',
                'HTTP_AUTHORIZATION' => 'Bearer {{token}}',
            ],
            'body'
        );
    }

    protected function createPsrServerRequest()
    {
        return new \GuzzleHttp\Psr7\ServerRequest(
            'GET',
            'https://example.org',
            [
                'Content-Type' => 'application/json',
                'Cookie' => 'key=value',
                'Authorization' => 'Bearer {{token}}',
            ],
            'body'
        );
    }

    protected function createPsrRequest()
    {
        return new \GuzzleHttp\Psr7\Request(
            'GET',
            'https://example.org',
            [
                'Content-Type' => 'application/json',
                'Cookie' => 'key=value',
                'Authorization' => 'Bearer {{token}}',
            ],
            'body'
        );
    }

    protected function createStringRequest(): string
    {
        return 'body';
    }
}
