<?php

namespace Garbetjie\Http\RequestLogging\Middleware;

use Closure;
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
            $entry = $this->logger->request($request, $this->logger::DIRECTION_OUT);

            return $handler($request, $options)->then(
                function ($response) use ($request, $entry) {
                    $this->logger->response($entry, $response);

                    return $response;
                },
                function ($e) use ($request, $entry) {
                    if ($response = method_exists($e, 'getResponse') ? $e->getResponse() : null) {
                        $this->logger->response($entry, $response);
                    }

                    throw $e;
                }
            );
        };
    }
}
