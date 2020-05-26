<?php

namespace Garbetjie\Http\RequestLogging\Psr;

use Garbetjie\Http\RequestLogging\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PsrRequestLoggingMiddleware extends Middleware implements MiddlewareInterface
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
        return $this->logRequest(
            $request,
            function($request) use ($handler) {
                return $handler->handle($request);
            },
            'in'
        );
    }
}
