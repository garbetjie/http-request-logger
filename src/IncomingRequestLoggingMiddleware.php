<?php

namespace Garbetjie\Http\RequestLogging;

use Closure;
use Illuminate\Http\Request;
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
        [$started, $id] = $this->logRequest($request, 'in');
        $response = $handler->handle($request);
        $this->logResponse($request, $response, $id, $started, 'in');

        return $response;
    }

    /**
     * Laravel middleware handler.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        [$started, $id] = $this->logRequest($request, 'in');
        $response = $next($request);
        $this->logResponse($request, $response, $id, $started, 'in');

        return $response;
    }
}
