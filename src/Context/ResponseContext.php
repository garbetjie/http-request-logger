<?php

namespace Garbetjie\RequestLogging\Http\Context;

use Garbetjie\RequestLogging\Http\ResponseEntry;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response as LaravelHttpClientResponse;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use function base64_encode;
use function Garbetjie\RequestLogging\Http\normalize_headers;
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
     * @param ResponseEntry $entry
     * @return array
     *@throws InvalidArgumentException
     *
     */
    public function __invoke(ResponseEntry $entry): array
    {
        switch (true) {
            case $entry->response() instanceof ResponseInterface:
                return $this->contextFromPSR($entry->response(), $entry);

            case $entry->response() instanceof SymfonyResponse:
                return $this->contextFromSymfony($entry->response(), $entry);

            case $entry->response() instanceof PromiseInterface:
                return $this->contextFromPromise($entry->response(), $entry);

			case $entry->response() instanceof LaravelHttpClientResponse:
				return $this->contextFromPSR($entry->response()->toPsrResponse(), $entry);

            case is_string($entry->response()):
                return $this->contextFromString($entry->response(), $entry);

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
	 * @param string $response
     * @param ResponseEntry $entry
	 *
     * @return array
     */
    protected function contextFromString(string $response, ResponseEntry $entry): array
    {
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
	 * @param ResponseInterface $response
     * @param ResponseEntry $entry
	 *
     * @return array
     */
    protected function contextFromPSR(ResponseInterface $response, ResponseEntry $entry): array
    {
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
	 * @param PromiseInterface $promise
     * @param ResponseEntry $entry
	 *
     * @return array
     */
    protected function contextFromPromise(PromiseInterface $promise, ResponseEntry $entry): array
    {
        return $this->contextFromPSR(
            $promise->wait(true),
			$entry
        );
    }

    /**
     * Extract context from a Symfony response (includes Laravel).
     *
	 * @param SymfonyResponse $response
     * @param ResponseEntry $entry
	 *
     * @return array
     */
    protected function contextFromSymfony(SymfonyResponse $response, ResponseEntry $entry): array
    {
        $body = $response->getContent();

        return [
            'id' => $entry->id(),
            'duration' => $entry->duration(),
            'status_code' => $response->getStatusCode(),
            'body_length' => strlen($body),
            'body' => base64_encode(substr($body, 0, $this->maxBodyLength)),
            'headers' => normalize_headers($response->headers->all()),
        ];
    }
}
