<?php

namespace Garbetjie\Http\RequestLogging\Context;

use Garbetjie\Http\RequestLogging\ResponseLogEntry;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Response as LaravelResponse;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use function base64_encode;
use function Garbetjie\Http\RequestLogging\normalize_headers;
use function get_class;
use function http_response_code;
use function is_scalar;
use function is_string;
use function sprintf;
use function stripos;
use function strlen;
use function substr;

class ResponseContext
{
    private $maxBodyLength;

    public function __construct(int $maxBodyLength = 16384)
    {
        $this->maxBodyLength = $maxBodyLength;
    }

    /**
     * @param ResponseLogEntry $entry
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public function __invoke(ResponseLogEntry $entry): array
    {
        switch (true) {
            case $entry->response() instanceof ResponseInterface:
                return $this->contextFromPSR($entry);

            case $entry->response() instanceof LaravelResponse:
                return $this->contextFromLaravel($entry);

            case $entry->response() instanceof PromiseInterface:
                return $this->contextFromPromise($entry);

            case is_string($entry->response()):
                return $this->contextFromString($entry);

            default:
                throw new InvalidArgumentException(
                    sprintf(
                        "Unknown response instance '%s' provided.",
                        is_scalar($entry->response()) ? $entry->response() : get_class($entry->response())
                    )
                );
        }
    }

    /**
     * Extract context from the given response, using server variables.
     *
     * @param ResponseLogEntry $entry
     * @return array
     */
    protected function contextFromString(ResponseLogEntry $entry): array
    {
        $response = $entry->response();
        $headers = [];

        foreach (headers_list() as $line) {
            if (stripos($line, ':') === false) {
                $name = $line;
                $value = '';
            } else {
                [$name, $value] = explode(':', $line);
            }

            $headers[$name][] = ltrim($value);
        }

        return [
            'id' => $entry->id(),
            'duration' => $entry->duration(),
            'status_code' => http_response_code(),
            'body_length' => strlen($response),
            'body' => base64_encode(substr($response, 0, $this->maxBodyLength)),
            'headers' => normalize_headers($headers),
        ];
    }

    /**
     * Extract context from a PSR-compliant response.
     *
     * @param ResponseLogEntry $entry
     * @return array
     */
    protected function contextFromPSR(ResponseLogEntry $entry): array
    {
        $response = $entry->response();

        $body = $response->getBody();
        $body->rewind();
        $contents = base64_encode($body->read($this->maxBodyLength));
        $body->rewind();

        return [
            'id' => $entry->id(),
            'duration' => $entry->duration(),
            'status_code' => $response->getStatusCode(),
            'body_length' => $body->getSize(),
            'body' => $contents,
            'headers' => normalize_headers($response->getHeaders()),
        ];
    }

    /**
     * Extract context from a Guzzle promise.
     *
     * @param ResponseLogEntry $entry
     * @return array
     */
    protected function contextFromPromise(ResponseLogEntry $entry): array
    {
        return $this->contextFromPSR(
            $entry->response()->wait(true)
        );
    }

    /**
     * Extract context from a Laravel response.
     *
     * @param ResponseLogEntry $entry
     * @return array
     */
    protected function contextFromLaravel(ResponseLogEntry $entry): array
    {
        $response = $entry->response();
        $body = $response->getContent();

        return [
            'id' => $entry->id(),
            'duration' => $entry->duration(),
            'status_code' => $response->status(),
            'body_length' => strlen($body),
            'body' => base64_encode(substr($body, 0, $this->maxBodyLength)),
            'headers' => normalize_headers($response->headers->all()),
        ];
    }
}
