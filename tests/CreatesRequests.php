<?php

namespace Garbetjie\RequestLogging\Http\Tests;

use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\ServerRequest as PsrServerRequest;
use Illuminate\Http\Request as LaravelRequest;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

trait CreatesRequests
{
    protected function createSymfonyRequest(): SymfonyRequest
    {
        return SymfonyRequest::create(
            'https://example.org/path?q=1',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer 123',
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_COOKIE' => 'key=value',
            ],
            'body'
        );
    }

    protected function createPsrServerRequest(): PsrServerRequest
    {
        return new PsrServerRequest(
            'GET',
            'https://example.org/path?q=1',
            [
                'Content-Type' => 'application/json',
                'Cookie' => 'key=value',
                'Authorization' => 'Bearer 456',
            ],
            'body'
        );
    }

    protected function createPsrRequest(): PsrRequest
    {
        return new PsrRequest(
            'GET',
            'https://example.org/path?q=1',
            [
                'Content-Type' => 'application/json',
                'Cookie' => 'key=value',
                'Authorization' => 'Bearer 789',
            ],
            'body'
        );
    }

    protected function createStringRequest(bool $https = true): string
    {
        $_SERVER['REQUEST_URI'] = '/path?q=1';
        $_SERVER['HTTP_HOST'] = 'example.org';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer 0ab';
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_COOKIE'] = 'key=value';

        if ($https) {
            $_SERVER['HTTPS'] = 'on';
        } else {
            unset($_SERVER['HTTPS']);
        }

        return 'body';
    }
}
