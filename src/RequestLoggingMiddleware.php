<?php

namespace Garbetjie\Http\RequestLogging;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use function array_reverse;
use function microtime;

class RequestLoggingMiddleware implements MiddlewareInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RequestContextExtractor
     */
    private $requestExtractor;

    /**
     * @var ResponseContextExtractor
     */
    private $responseExtractor;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->requestExtractor = new RequestContextExtractor();
        $this->responseExtractor = new ResponseContextExtractor();
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
        return $this->run($request, $next, 'in');
    }

    /**
     * PSR-compliant middleware handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->run(
            $request,
            function($request) use ($handler) {
                return $handler->handle($request);
            },
            'in'
        );
    }

    /**
     * Guzzle middleware handler.
     *
     * @param callable $handler
     * @return Closure
     */
    public function __invoke(callable $handler)
    {
        return function ($request, array $options) use ($handler) {
            return $this->run(
                $request,
                function ($request) use ($handler, $options) {
                    return $handler($request, $options);
                },
                'out'
            );
        };
    }

    /**
     * @param RequestInterface|Request $request
     * @param callable $handler
     * @param string $direction
     * @return Response|ResponseInterface
     */
    private function run($request, callable $handler, $direction)
    {
        $id = generate_id();
        $verbs = ['received', 'sent'];

        if ($direction === 'out') {
            $verbs = array_reverse($verbs);
        }

        $context = $this->requestExtractor->extract($request);
        $this->logger->log('debug', 'http request ' . $verbs[0], ['id' => $id] + $context);

        $started = microtime(true);
        $response = $handler($request);
        $duration = microtime(true) - $started;

        $context = $this->responseExtractor->extract($response);
        $this->logger->log('debug', 'http response ' . $verbs[1], ['id' => $id, 'duration' => $duration] + $context);

        return $response;
    }
}
