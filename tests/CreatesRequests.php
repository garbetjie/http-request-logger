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
                'HTTP_AUTHORIZATION' => 'Bearer 123',
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
                'Authorization' => 'Bearer 456',
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
                'Authorization' => 'Bearer 789',
            ],
            'body'
        );
    }

    protected function createStringRequest(): string
    {
        header_remove();
        header('Authorization: Bearer 0ab');
        header('Content-Type: application/json');
        header('Cookie: key=value');

        return 'body';
    }
}
