<?php

namespace Garbetjie\Http\RequestLogging;

use Illuminate\Http\Request as LaravelRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
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
     * @var RequestInterface|ServerRequestInterface|LaravelRequest
     */
    protected $request;

    /**
     * @param RequestInterface|ServerRequestInterface|LaravelRequest|string $request
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
     * @return LaravelRequest|RequestInterface|ServerRequestInterface|string
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
