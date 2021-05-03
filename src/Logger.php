<?php

namespace Garbetjie\Http\RequestLogging;

use Garbetjie\Http\RequestLogging\Context\SafeRequestContext;
use Garbetjie\Http\RequestLogging\Context\SafeResponseContext;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use SplObjectStorage;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use function call_user_func;
use function in_array;
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
     * @var string|int
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
     * @var callable[]
     */
    protected $messages = [];

    /**
     * @var callable
     */
    protected $id;

    /**
     * @var SplObjectStorage
     */
    protected $startedAt;

    public const DIRECTION_IN = 'in';

    public const DIRECTION_OUT = 'out';

    /**
     * @param LoggerInterface $logger
     * @param string|int $level
     */
    public function __construct(LoggerInterface $logger, $level = LogLevel::DEBUG)
    {
        // Set logger and level.
        $this->logger = $logger;
        $this->level = $level;

        // Create the map of startedAt timestamps.
        $this->startedAt = new SplObjectStorage();

        // Set default extractors.
        $this->context(new SafeRequestContext(), new SafeResponseContext());

        // Set default message handler.
        $this->message(
            function (RequestEntry $entry) {
                return [
                    Logger::DIRECTION_IN => 'http request received',
                    Logger::DIRECTION_OUT => 'http request sent',
                ][$entry->direction()];
            },
            function (ResponseEntry $entry) {
                return [
                    Logger::DIRECTION_IN => 'http response received',
                    Logger::DIRECTION_OUT => 'http response sent',
                ][$entry->direction()];
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
     * @param callable|null $request
     * @param callable|null $response
     *
     * @return $this
     */
    public function message(?callable $request, ?callable $response): Logger
    {
        if ($request !== null) {
            $this->messages['requests'] = $request;
        }

        if ($response !== null) {
            $this->messages['responses'] = $response;
        }

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
     * @return RequestEntry
     */
    public function request($request, string $direction): RequestEntry
    {
        $direction = strtolower($direction);

        // Ensure a valid direction is given.
        if (!in_array($direction, [Logger::DIRECTION_IN, Logger::DIRECTION_OUT])) {
            throw new InvalidArgumentException("Unexpected request direction '{$direction}'.");
        }

        // Create the request's log entry.
        $logEntry = new RequestEntry(
            $request,
            call_user_func($this->id),
            $direction
        );

        // Only log the request if requests should be logged.
        if (call_user_func($this->enabled['requests'], $logEntry)) {
            $this->logger->log(
                $this->level,
                call_user_func($this->messages['requests'], $logEntry),
                call_user_func($this->context['requests'], $logEntry) ?: []
            );
        }

        // This is only populated here because we don't know how long the call to $this->logger->log() might take.
        // If the log() call takes 1s, this will misrepresent the duration of the request, hence why we only populate
        // it later.
        // It would be nice for the context extractors and togglers to receive the proper values, but ¯\_(ツ)_/¯
        $this->startedAt[$logEntry] = microtime(true);

        return $logEntry;
    }

    /**
     * @param RequestEntry $request
     * @param ResponseInterface|SymfonyResponse|string $response
     *
     * @return void
     */
    public function response(RequestEntry $request, $response): void
    {
        $duration = isset($this->startedAt[$request]) ? microtime(true) - $this->startedAt[$request] : null;
        $logEntry = new ResponseEntry($request, $response, $duration);

        // Ensure the reference in the object map is removed.
        unset($this->startedAt[$request]);

        // Only log the response if responses should be logged.
        if (call_user_func($this->enabled['responses'], $logEntry)) {
            $this->logger->log(
                $this->level,
                call_user_func($this->messages['responses'], $logEntry),
                call_user_func($this->context['responses'], $logEntry) ?: []
            );
        }
    }
}
