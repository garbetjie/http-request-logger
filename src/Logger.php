<?php

namespace Garbetjie\Http\RequestLogging;

use Garbetjie\Http\RequestLogging\Context\SafeRequestContext;
use Garbetjie\Http\RequestLogging\Context\SafeResponseContext;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
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
    protected $context = [];

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
        $this->context(new SafeRequestContext(), new SafeResponseContext());

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

    /**
     * Set the callable used to generate an ID to link requests & responses together.
     *
     * @param callable $handler
     *
     * @return $this
     */
    public function id(callable $handler): Logger
    {
        $this->id = $handler;

        return $this;
    }

    /**
     * Set the function used to generate the log message.
     *
     * @param callable $handler
     *
     * @return $this
     */
    public function message(callable $handler): Logger
    {
        $this->message = $handler;

        return $this;
    }

    /**
     * Set the functions to use to extract context for each request/response.
     *
     * @param callable|null $request
     * @param callable|null $response
     *
     * @return $this
     */
    public function context(?callable $request, ?callable $response): Logger
    {
        if ($request !== null) {
            $this->context['requests'] = $request;
        }

        if ($response !== null) {
            $this->context['responses'] = $response;
        }

        return $this;
    }

    /**
     * Set the function to use to determine whether a request or response should be logged.
     *
     * @param null|bool|callable $requests
     * @param null|bool|callable $responses
     *
     * @return $this
     */
    public function enabled($requests, $responses): Logger
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

        return $this;
    }

    /**
     * @param RequestInterface|SymfonyRequest|ServerRequestInterface|string $request
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
            $context = call_user_func($this->context['requests'], $request, $direction) ?: [];
            $this->logger->log($this->level, $message, ['id' => $id] + $context);
        }

        return new LoggedRequest($id, $direction, microtime(true));
    }

    /**
     * @param RequestInterface|SymfonyRequest|ServerRequestInterface|string $request
     * @param ResponseInterface|SymfonyResponse|string $response
     * @param LoggedRequest $entry
     *
     * @return void
     */
    public function response($request, $response, LoggedRequest $entry): void
    {
        $direction = $entry->direction() === 'in' ? 'out' : 'in';
        $message = call_user_func($this->message, __FUNCTION__, $direction);
        $duration = microtime(true) - $entry->started();

        if (call_user_func($this->enabled['responses'], $response, $request, $direction)) {
            $context = call_user_func($this->context['responses'], $response, $request, $direction) ?: [];
            $this->logger->log($this->level, $message, ['id' => $entry->id(), 'duration' => $duration] + $context);
        }
    }
}
