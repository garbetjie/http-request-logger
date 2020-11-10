<?php

namespace Garbetjie\Http\RequestLogging;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use function call_user_func;
use function func_get_args;
use function is_callable;
use function microtime;

abstract class Middleware
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $level;

    /**
     * @var callable
     */
    protected $requestExtractor;

    /**
     * @var callable
     */
    protected $responseExtractor;

    /**
     * @var callable
     */
    protected $requestLoggingEnabled;

    /**
     * @var callable
     */
    protected $responseLoggingEnabled;

    /**
     * @var array
     */
    protected $messages = [
        'incoming request' => 'http request received',
        'outgoing response' => 'http response sent',

        'outgoing request' => 'http request sent',
        'incoming response' => 'http response received',
    ];

    /**
     * @param LoggerInterface $logger
     * @param string $level
     */
    public function __construct(LoggerInterface $logger, string $level)
    {
        $this->logger = $logger;
        $this->level = $level;

        // Set the context extractors.
        $this->requestContextExtractor(new SafeRequestContextExtractor());
        $this->responseContextExtractor(new SafeResponseContextExtractor());

        // By default, always log messages.
        $this->enableRequestLogging(true);
        $this->enableResponseLogging(true);
    }

    /**
     * Sets the deciders used to decide whether or not to log a request or a response.
     *
     * @param callable|null $request
     * @param callable|null $response
     *
     * @return static
     * @deprecated
     */
    public function setDeciders(?callable $request, ?callable $response)
    {
        if ($request) {
            $this->enableRequestLogging($request);
        }

        if ($response) {
            $this->enableResponseLogging($response);
        }

        return $this;
    }

    /**
     * Indicates whether or not request logging should be enabled.
     *
     * @param bool|callable $enabled
     *
     * @return static
     */
    public function enableRequestLogging($enabled)
    {
        if (!is_callable($enabled)) {
            $this->requestLoggingEnabled = function() use ($enabled) {
                return $enabled;
            };
        } else {
            $this->requestLoggingEnabled = $enabled;
        }

        return $this;
    }

    /**
     * Indicates whether or not response logging should be enabled.
     *
     * @param bool|callable $enabled
     *
     * @return static
     */
    public function enableResponseLogging($enabled)
    {
        if (!is_callable($enabled)) {
            $this->responseLoggingEnabled = function() use ($enabled) {
                return $enabled;
            };
        } else {
            $this->responseLoggingEnabled = $enabled;
        }

        return $this;
    }

    /**
     * Alias of $this->setDeciders().
     *
     * @alias Middleware::setDeciders
     * @param callable|null $request
     * @param callable|null $response
     *
     * @return static
     * @deprecated
     */
    public function withDeciders(?callable $request, ?callable $response)
    {
        return $this->setDeciders($request, $response);
    }

    /**
     * Sets the extractors used when extracting context for log messages.
     *
     * The callable passed in $request will be used to extract the context for a request. The callable in $response
     * will be used to extract context for responses.
     *
     * @param callable|null $request
     * @param callable|null $response
     *
     * @deprecated
     * @return static
     */
    public function setExtractors(?callable $request, ?callable $response)
    {
        if ($request) {
            $this->requestExtractor = $request;
        }

        if ($response) {
            $this->responseExtractor = $response;
        }

        return $this;
    }

    /**
     * Sets the extractor to use when extracting context for requests.
     *
     * @param callable $extractor
     *
     * @return static
     */
    public function requestContextExtractor(callable $extractor)
    {
        $this->requestExtractor = $extractor;

        return $this;
    }

    /**
     * Sets the extractor to use when extracting context for responses.
     *
     * @param callable $extractor
     *
     * @return static
     */
    public function responseContextExtractor(callable $extractor)
    {
        $this->responseExtractor = $extractor;

        return $this;
    }

    /**
     * Alias of $this->setExtractors().
     *
     * @alias Middleware::setDeciders()
     * @param callable|null $request
     * @param callable|null $response
     *
     * @deprecated
     * @return static
     */
    public function withExtractors(?callable $request, ?callable $response)
    {
        return $this->setExtractors($request, $response);
    }

    /**
     * @param RequestInterface|Request|ServerRequestInterface $request
     * @param string $direction
     *
     * @return array
     */
    protected function logRequest($request, string $direction)
    {
        $id = generate_id();
        $message = $this->messages[$direction === 'in' ? 'incoming request' : 'outgoing request'];

        if (call_user_func($this->requestLoggingEnabled, $request, $direction)) {
            $context = call_user_func($this->requestExtractor, $request, $direction);
            $this->logger->log($this->level, $message, ['id' => $id] + $context);
        }

        $started = microtime(true);

        return [$started, $id];
    }

    /**
     * @param RequestInterface|Request|ServerRequestInterface $request
     * @param ResponseInterface|Response $response
     * @param string $id
     * @param float $started
     * @param string $requestDirection
     */
    protected function logResponse($request, $response, string $id, float $started, string $requestDirection)
    {
        $direction = $requestDirection === 'in' ? 'out' : 'in';
        $message = $this->messages[$direction === 'out' ? 'outgoing response' : 'incoming response'];
        $duration = microtime(true) - $started;

        if (call_user_func($this->responseLoggingEnabled, $response, $request, $direction)) {
            $context = call_user_func($this->responseExtractor, $response, $request, $direction);
            $this->logger->log($this->level, $message, ['id' => $id, 'duration' => $duration] + $context);
        }
    }

    /**
     * Overrides the messages that are used when logging incoming/outgoing requests & responses.
     *
     * @param string|null $incomingRequestMessage - Incoming requests (received by framework middleware).
     * @param string|null $outgoingResponseMessage - Outgoing response to an incoming request.
     * @param string|null $outgoingRequestMessage - Outgoing requests (sent by HTTP clients like Guzzle).
     * @param string|null $incomingResponseMessage - Incoming response to an outgoing request.
     *
     * @return static
     */
    public function setMessages(
        ?string $incomingRequestMessage = null,
        ?string $outgoingResponseMessage = null,
        ?string $outgoingRequestMessage = null,
        ?string $incomingResponseMessage = null
    ) {
        if ($incomingRequestMessage !== null) {
            $this->messages['incoming request'] = $incomingRequestMessage;
        }

        if ($outgoingResponseMessage !== null) {
            $this->messages['outgoing response'] = $outgoingResponseMessage;
        }

        if ($outgoingRequestMessage !== null) {
            $this->messages['outgoing request'] = $outgoingRequestMessage;
        }

        if ($incomingResponseMessage !== null) {
            $this->messages['incoming response'] = $incomingResponseMessage;
        }

        return $this;
    }

    /**
     * Alias of $this->setMessages().
     *
     * @alias Middleware::setMessages()
     * @param string|null $incomingRequestMessage
     * @param string|null $outgoingResponseMessage
     * @param string|null $outgoingRequestMessage
     * @param string|null $incomingResponseMessage
     *
     * @return static
     */
    public function withMessages(
        ?string $incomingRequestMessage = null,
        ?string $outgoingResponseMessage = null,
        ?string $outgoingRequestMessage = null,
        ?string $incomingResponseMessage = null
    ) {
        return $this->setMessages(...func_get_args());
    }
}
