<?php

namespace Garbetjie\RequestLogging\Http;

use Illuminate\Http\Client\Request as LaravelHttpClientRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use function strtolower;

final class RequestEntry
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $direction;

    /**
     * @var LaravelHttpClientRequest|RequestInterface|ServerRequestInterface|SymfonyRequest
     */
    protected $request;

    /**
     * @param LaravelHttpClientRequest|RequestInterface|ServerRequestInterface|SymfonyRequest|string $request
     * @param string $id
     * @param string $direction
     */
    public function __construct($request, string $id, string $direction)
    {
        $this->request = $request;
        $this->id = $id;
        $this->direction = strtolower($direction);
    }

    /**
     * @return LaravelHttpClientRequest|SymfonyRequest|RequestInterface|ServerRequestInterface|string
     */
    public function request()
    {
        return $this->request;
    }

    /**
     * @return string
     */
    public function direction(): string
    {
        return $this->direction;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }
}
