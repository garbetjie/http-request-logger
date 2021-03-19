<?php

namespace Garbetjie\Http\RequestLogging;

use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response as LaravelResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use function call_user_func;
use function is_callable;
use function microtime;
use function strtolower;

class Logger
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var mixed
     */
    protected $level;

    /**
     * @var callable[]
     */
    protected $extractors = [];

    /**
     * @var callable[]
     */
    protected $enabled = [];

    /**
     * @var callable
     */
    protected $message;

    /**
     * @var callable
     */
    protected $id;

    public const DIRECTION_IN = 'in';

    public const DIRECTION_OUT = 'out';

    /**
     * @param LoggerInterface $logger
     * @param mixed $level
     */
    public function __construct(LoggerInterface $logger, $level)
    {
        // Set logger and level.
        $this->logger = $logger;
        $this->level = $level;

        // Set default extractors.
        $this->extractors(new SafeRequestContextExtractor(), new SafeResponseContextExtractor());

        // Set default message handler.
        $this->message(
            function(string $what, string $direction) {
                return [
                    'request:in' => 'http request received',
                    'request:out' => 'http request sent',
                    'response:in' => 'http response received',
                    'response:out' => 'http response sent',
                ]["{$what}:{$direction}"];
            }
        );

        // Enable request & response logging by default.
        $this->enabled(true, true);

        // Set default ID generation.
        $this->id(
            function() {
                return generate_id();
            }
        );
    }

    public function id(callable $handler)
    {
        $this->id = $handler;
    }

    public function message(callable $handler)
    {
        $this->message = $handler;
    }

    public function extractors(?callable $request, ?callable $response): Logger
    {
        if ($request !== null) {
            $this->extractors['requests'] = $request;
        }

        if ($response !== null) {
            $this->extractors['responses'] = $response;
        }

        return $this;
    }

    /**
     * @param null|bool|callable $requests
     * @param null|bool|callable $responses
     */
    public function enabled($requests, $responses)
    {
        // Determine whether requests are enabled.
        if ($requests !== null) {
            $this->enabled['requests'] = is_callable($requests)
                ? $requests
                : function() use ($requests) { return (bool)$requests; };
        }

        // Determine whether responses are enabled.
        if ($responses !== null) {
            $this->enabled['responses'] = is_callable($responses)
                ? $responses
                : function() use ($responses) { return (bool)$responses; };
        }
    }

    /**
     * @param RequestInterface|LaravelRequest|ServerRequestInterface|string $request
     * @param string $direction
     *
     * @return LoggedRequest
     */
    public function request($request, string $direction): LoggedRequest
    {
        $id = call_user_func($this->id);
        $direction = strtolower($direction);
        $message = call_user_func($this->message, __FUNCTION__, $direction);

        if (call_user_func($this->enabled['requests'], $request, $direction)) {
            $context = call_user_func($this->extractors['requests'], $request, $direction);
            $this->logger->log($this->level, $message, ['id' => $id] + $context);
        }

        return new LoggedRequest($id, $direction, microtime(true));
    }

    /**
     * @param RequestInterface|LaravelRequest|ServerRequestInterface|string $request
     * @param ResponseInterface|LaravelResponse|string $response
     * @param LoggedRequest $entry
     */
    public function response($request, $response, LoggedRequest $entry)
    {
        $direction = $entry->direction() === 'in' ? 'out' : 'in';
        $message = call_user_func($this->message, __FUNCTION__, $direction);
        $duration = microtime(true) - $entry->started();

        if (call_user_func($this->enabled['responses'], $response, $request, $direction)) {
            $context = call_user_func($this->extractors['responses'], $response, $request, $direction);
            $this->logger->log($this->level, $message, ['id' => $entry->id(), 'duration' => $duration] + $context);
        }
    }
}
