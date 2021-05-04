<?php

namespace Garbetjie\RequestLogging\Http;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class ResponseEntry
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var float
     */
    protected $duration;

    /**
     * @var SymfonyRequest|RequestInterface|ServerRequestInterface
     */
    protected $request;

    /**
     * @var SymfonyResponse|ResponseInterface|string
     */
    protected $response;

    /**
     * @var string
     */
    protected $direction;

    /**
     * @param RequestEntry $request
     * @param ResponseInterface|SymfonyResponse|string $response
     * @param float|null $duration
     */
    public function __construct(RequestEntry $request, $response, ?float $duration)
    {
        $this->id = $request->id();
        $this->duration = $duration;
        $this->request = $request->request();
        $this->response = $response;
        $this->direction = $request->direction() === Logger::DIRECTION_IN ? Logger::DIRECTION_OUT : Logger::DIRECTION_IN;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function duration(): float
    {
        return $this->duration;
    }

    /**
     * @return SymfonyRequest|RequestInterface|ServerRequestInterface|string
     */
    public function request()
    {
        return $this->request;
    }

    /**
     * @return SymfonyResponse|ResponseInterface|PromiseInterface|string
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * @return string
     */
    public function direction(): string
    {
        return $this->direction;
    }
}
