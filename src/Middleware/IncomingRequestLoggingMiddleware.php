<?php

namespace Garbetjie\Http\RequestLogging\Middleware;

use Closure;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IncomingRequestLoggingMiddleware extends Middleware implements MiddlewareInterface
{
    /**
     * PSR-compliant middleware handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $logged = $this->logger->request($request, $this->logger::DIRECTION_IN);
        $response = $handler->handle($request);
        $this->logger->response($request, $response, $logged);

        return $response;
    }

    /**
     * Laravel middleware handler.
     *
     * @param LaravelRequest $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        $logged = $this->logger->request($request, $this->logger::DIRECTION_IN);
        $response = $next($request);
        $this->logger->response($request, $response, $logged);

        return $response;
    }
}