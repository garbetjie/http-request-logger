<?php

namespace Garbetjie\Http\RequestLogging;

use Garbetjie\Http\RequestLogging\ContextExtractorInterface;
use Garbetjie\Http\RequestLogging\LoggingAllowedDeciderInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use function call_user_func;
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
        $this->withExtractors(new RequestContextExtractor(), new ResponseContextExtractor());

        // By default, always log messages.
        $this->withDeciders(
            function() {
                return true;
            },
            function() {
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
    public function withDeciders(?callable $request, ?callable $response)
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
    public function withExtractors(?callable $request, ?callable $response)
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
     * @param RequestInterface|Request $request
     * @param callable $handler
     * @param string $requestDirection
     * @return Response|ResponseInterface
     */
    protected function logRequest($request, callable $handler, $requestDirection)
    {
        $id = generate_id();

        if ($requestDirection === 'in') {
            $requestMessage = $this->messages['incoming request'];
            $responseMessage = $this->messages['outgoing response'];
        } else {
            $requestMessage = $this->messages['outgoing request'];
            $responseMessage = $this->messages['incoming response'];
        }

        if (call_user_func($this->requestDecider, $request, $requestDirection)) {
            $context = call_user_func($this->requestExtractor, $request, $requestDirection);
            $this->logger->log($this->level, $requestMessage, ['id' => $id] + $context);
        }

        $started = microtime(true);
        $response = $handler($request);
        $duration = microtime(true) - $started;

        if (call_user_func($this->responseDecider, $response, $request, $requestDirection === 'in' ? 'out' : 'in')) {
            $context = call_user_func($this->responseExtractor, $response, $request, $requestDirection === 'in' ? 'out' : 'in');
            $this->logger->log($this->level, $responseMessage, ['id' => $id, 'duration' => $duration] + $context);
        }

        return $response;
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
    public function withMessages(?string $incomingRequestMessage = null, ?string $outgoingResponseMessage = null, ?string $outgoingRequestMessage = null, ?string $incomingResponseMessage = null)
    {
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
}
