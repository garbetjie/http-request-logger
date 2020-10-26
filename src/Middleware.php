<?php

namespace Garbetjie\Http\RequestLogging;

use Illuminate\Http\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use function call_user_func;
use function func_get_args;
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
    protected $requestDecider;

    /**
     * @var callable
     */
    protected $responseDecider;

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
        $this->setExtractors(new RequestContextExtractor(), new ResponseContextExtractor());

        // By default, always log messages.
        $this->setDeciders(
            function () {
                return true;
            },
            function () {
                return true;
            }
        );
    }

    /**
     * Sets the deciders used to decide whether or not to log a request or a response.
     *
     * @param callable|null $request
     * @param callable|null $response
     *
     * @return static
     */
    public function setDeciders(?callable $request, ?callable $response)
    {
        if ($request) {
            $this->requestDecider = $request;
        }

        if ($response) {
            $this->responseDecider = $response;
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
     * Alias of $this->>setExtractors().
     *
     * @alias Middleware::setDeciders()
     * @param callable|null $request
     * @param callable|null $response
     *
     * @return static
     */
    public function withExtractors(?callable $request, ?callable $response)
    {
        return $this->setExtractors($request, $response);
    }

    /**
     * @param RequestInterface|Request $request
     * @param string $direction
     *
     * @return array
     */
    protected function logRequest($request, $direction)
    {
        $id = generate_id();
        $message = $this->messages[$direction === 'in' ? 'incoming request' : 'outgoing request'];

        if (call_user_func($this->requestDecider, $request, $direction)) {
            $context = call_user_func($this->requestExtractor, $request, $direction);
            $this->logger->log($this->level, $message, ['id' => $id] + $context);
        }

        $started = microtime(true);

        return [$started, $id];
    }

    protected function logResponse($request, $response, $id, $started, $requestDirection)
    {
        $direction = $requestDirection === 'in' ? 'out' : 'in';
        $message = $this->messages[$direction === 'out' ? 'outgoing response' : 'incoming response'];
        $duration = microtime(true) - $started;

        if (call_user_func($this->responseDecider, $response, $request, $direction)) {
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
