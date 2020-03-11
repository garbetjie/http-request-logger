<?php

namespace Garbetjie\Http\RequestLogging\Middleware;

use Closure;

class GuzzleMiddleware extends Middleware
{
    /**
     * Guzzle middleware handler.
     *
     * @param callable $handler
     * @return Closure
     */
    public function __invoke(callable $handler)
    {
        return function ($request, array $options) use ($handler) {
            return $this->logRequest(
                $request,
                function ($request) use ($handler, $options) {
                    return $handler($request, $options);
                },
                'out'
            );
        };
    }
}
