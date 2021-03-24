<?php

namespace Garbetjie\Http\RequestLogging\Context;

use GuzzleHttp\Promise\PromiseInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use function base64_encode;
use function Garbetjie\Http\RequestLogging\normalize_headers;
use function get_class;
use function http_response_code;
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
     * @param ResponseInterface|Response|string $response
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public function __invoke($response): array
    {
        switch (true) {
            case $response instanceof ResponseInterface:
                return $this->extractResponsePSR($response);

            case $response instanceof Response:
                return $this->extractResponseLaravel($response);

            case $response instanceof PromiseInterface:
                return $this->extractResponsePromise($response);

            case is_string($response):
                return $this->extractResponseString($response);

            default:
                throw new InvalidArgumentException(sprintf('Unknown response instance "%s" provided.', get_class($response)));
        }
    }

    /**
     * @param string $response
     * @return array
     */
    protected function extractResponseString(string $response): array
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
            'status_code' => http_response_code(),
            'body_length' => strlen($response),
            'body' => base64_encode(substr($response, 0, $this->maxBodyLength)),
            'headers' => normalize_headers($headers),
        ];
    }

    /**
     * @param ResponseInterface $response
     * @return array
     */
    protected function extractResponsePSR(ResponseInterface $response)
    {
        $body = $response->getBody();
        $body->rewind();
        $contents = base64_encode($body->read($this->maxBodyLength));
        $body->rewind();

        return [
            'status_code' => $response->getStatusCode(),
            'body_length' => $body->getSize(),
            'body' => $contents,
            'headers' => normalize_headers($response->getHeaders()),
        ];
    }

    /**
     * @param PromiseInterface $promise
     * @return array
     */
    protected function extractResponsePromise(PromiseInterface $promise)
    {
        return $this->extractResponsePSR(
            $promise->wait(true)
        );
    }

    /**
     * @param Response $response
     * @return array
     */
    protected function extractResponseLaravel(Response $response)
    {
        $body = $response->getContent();

        return [
            'status_code' => $response->getStatusCode(),
            'body_length' => strlen($body),
            'body' => base64_encode(substr($body, 0, $this->maxBodyLength)),
            'headers' => normalize_headers($response->headers->all()),
        ];
    }
}
