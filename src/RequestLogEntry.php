<?php

namespace Garbetjie\Http\RequestLogging;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use function strtolower;

final class RequestLogEntry
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
     * @var RequestInterface|ServerRequestInterface|SymfonyRequest
     */
    protected $request;

    /**
     * @param RequestInterface|ServerRequestInterface|SymfonyRequest|string $request
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
     * @return SymfonyRequest|RequestInterface|ServerRequestInterface|string
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
