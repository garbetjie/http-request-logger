<?php

namespace Garbetjie\Http\RequestLogging;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response as LaravelResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function microtime;

final class ResponseLogEntry
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
     * @var LaravelRequest|RequestInterface|ServerRequestInterface
     */
    protected $request;

    /**
     * @var LaravelResponse|ResponseInterface|string
     */
    protected $response;

    /**
     * @var string
     */
    protected $direction;

    /**
     * @param RequestLogEntry $request
     * @param ResponseInterface|LaravelResponse|string $response
     * @param float|null $duration
     */
    public function __construct(RequestLogEntry $request, $response, ?float $duration)
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
     * @return LaravelRequest|RequestInterface|ServerRequestInterface|string
     */
    public function request()
    {
        return $this->request;
    }

    /**
     * @return LaravelResponse|ResponseInterface|PromiseInterface|string
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
