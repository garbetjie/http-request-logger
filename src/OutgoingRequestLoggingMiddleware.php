<?php

namespace Garbetjie\Http\RequestLogging;

use Closure;
use GuzzleHttp\Exception\GuzzleException;
use function method_exists;

class OutgoingRequestLoggingMiddleware extends Middleware
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
            [$started, $id] = $this->logRequest($request, 'out');

            return $handler($request, $options)->then(
                function ($response) use ($started, $id, $request) {
                    $this->logResponse($request, $response, $id, $started, 'out');

                    return $response;
                },
                function ($e) use ($request, $started, $id) {
                    if ($response = method_exists($e, 'getResponse') ? $e->getResponse() : null) {
                        $this->logResponse($request, $response, $id, $started, 'out');
                    }

                    throw $e;
                }
            );
        };
    }
}
